const accountRaw = localStorage.getItem('hamsterAccount');

if (!accountRaw) {
  window.location.href = 'account.html';
}

const account = JSON.parse(accountRaw || '{}');
const logout = document.getElementById('profileLogout');
let carsExpanded = false;
let skillsExpanded = false;
let vehicleNames = {};
let currentCars = [];
let currentSkills = {};

renderProfile(account);
loadVehicleNames();
loadCars(account);
loadFamily(account);

logout.addEventListener('click', () => {
  localStorage.removeItem('hamsterAccount');
  window.location.href = 'account.html';
});

function renderProfile(data) {
  const level = Number(data.level || 0);
  const rub = Number(data.rub || data.rubs || 0);
  const email = normalizeText(data.email, 'Не указана');

  setText('profileName', data.name || 'Игрок');
  setText('profileSkin', `skin ${data.skin || 0}`);
  setText('profileLevel', `${level} lvl`);
  setText('profileRub', `${rub.toLocaleString('ru-RU')} rub`);
  setText('profileMoney', formatMoney(data.money));
  setText('profileBank', formatMoney(data.bank));
  setHealth(data.health);

  setText('securityEmail', email);
  setText('securityLastLogin', formatDate(data.lastLogin));
  setText('characterLevel', level);
  setText('characterJob', jobLabel(data.job));
  setText('characterJobCard', jobLabel(data.job));
  setText('characterPhone', normalizeText(data.phone, 'Нет'));
  setText('characterVip', Number(data.premium) > 0 ? 'Есть' : 'Нет');
  const vipBadge = document.getElementById('characterVipBadge');
  if (vipBadge) vipBadge.classList.toggle('hidden', !(Number(data.premium) > 0));
  setText('characterFamily', normalizeText(data.familyName, 'Не состоит'));
  setText('characterFamilyCard', normalizeText(data.familyName, 'Не состоит'));
  setText('profileMoneyCard', formatMoney(data.money));
  setText('profileBankCard', formatMoney(data.bank));
  setText('characterFamilyRank', familyRankLabel(data));
  setText('characterWarn', data.warn || 0);
  setText('characterSuspect', data.suspect || 0);
  setText('characterHours', `${data.gameForHour || 0} ч.`);
  setText('characterVoennik', Number(data.voennik) > 0 ? 'Есть' : 'Нет');

  renderCars(data.cars || []);
  renderSkills(data.skills || {});
}

function updateExpandState() {
  const grid = document.querySelector('.prof-grid');
  if (grid) grid.classList.toggle('prof-grid--expanded', carsExpanded || skillsExpanded);
}

function renderCars(cars) {
  currentCars = cars;
  const list = document.getElementById('profileCars');
  if (!list) return;

  if (!cars.length) {
    list.innerHTML = '<div class="prof-empty">Транспорт не найден.</div>';
    list.parentElement?.querySelectorAll('.prof-more').forEach((button) => button.remove());
    return;
  }

  const visibleCars = carsExpanded ? cars : cars.slice(0, 4);
  list.innerHTML = visibleCars.map(renderCar).join('');

  const parent = list.parentElement;
  parent?.querySelectorAll('.prof-more').forEach((button) => button.remove());

  if (cars.length > 4) {
    const label = carsExpanded ? 'Свернуть' : `Показать больше (${cars.length - 4})`;
    const toggleButton = document.createElement('button');
    toggleButton.type = 'button';
    toggleButton.className = 'prof-more';
    toggleButton.id = 'profileToggleCars';
    toggleButton.textContent = label;
    toggleButton.addEventListener('click', () => {
      if (carsExpanded) {
        carsExpanded = false;
        updateExpandState();
        renderCars(currentCars);
        return;
      }

      openListModal({
        title: 'Транспорт',
        items: cars.map(renderCar),
        closeOnBackdrop: true,
      });
    });
    list.after(toggleButton);
  }
  updateExpandState();
}

function renderCar(car) {
  const plate = carPlate(car);

  return `
    <article class="prof-car">
      <div class="prof-car-icon" aria-hidden="true">
        <svg viewBox="0 0 96 64" fill="none" stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M18 39l9-17h39l12 17"></path>
          <path d="M10 39h76v14H10z"></path>
          <circle cx="28" cy="53" r="7"></circle>
          <circle cx="68" cy="53" r="7"></circle>
        </svg>
      </div>
      <div class="prof-car-info">
        <b>${vehicleName(car.modelId)}</b>
        <span>${plate !== 'Отсутствует' ? plate : '—'} · ID ${car.modelId}</span>
        <small>${Math.round(car.fuel || 0)}л · ${Math.round(car.mileage || 0).toLocaleString('ru-RU')} км</small>
      </div>
    </article>
  `;
}

function openListModal({ title, items, closeOnBackdrop = true }) {
  const backdrop = document.createElement('div');
  backdrop.className = 'prof-modal-backdrop';

  const modal = document.createElement('div');
  modal.className = 'prof-modal';
  modal.setAttribute('role', 'dialog');
  modal.setAttribute('aria-modal', 'true');
  modal.setAttribute('aria-label', title);

  const header = document.createElement('div');
  header.className = 'prof-modal-header';

  const heading = document.createElement('h3');
  heading.className = 'prof-modal-title';
  heading.textContent = title;

  const closeButton = document.createElement('button');
  closeButton.type = 'button';
  closeButton.className = 'prof-modal-close';
  closeButton.setAttribute('aria-label', 'Закрыть');
  closeButton.textContent = '×';
  closeButton.addEventListener('click', () => backdrop.remove());

  const body = document.createElement('div');
  body.className = 'prof-modal-body';

  const content = document.createElement('div');
  content.className = 'prof-modal-list';
  content.innerHTML = Array.isArray(items) ? items.join('') : items;

  header.append(heading, closeButton);
  body.appendChild(content);
  modal.append(header, body);
  backdrop.appendChild(modal);
  document.body.appendChild(backdrop);

  if (closeOnBackdrop) {
    backdrop.addEventListener('click', (event) => {
      if (event.target === backdrop) {
        backdrop.remove();
      }
    });
  }

  window.addEventListener('keydown', function onKeyDown(event) {
    if (event.key === 'Escape') {
      backdrop.remove();
      window.removeEventListener('keydown', onKeyDown);
    }
  }, { once: true });
}

function renderSkills(skills) {
  currentSkills = skills;
  const list = document.getElementById('profileSkills');
  if (!list) return;

  const allSkills = [
    ['colt', 'Colt', 'Владение Colt'],
    ['deagle', 'Deagle', 'Владение Deagle'],
    ['shotgun', 'Shotgun', 'Владение Shotgun'],
    ['sdPistol', 'SD Pistol', 'Владение SD Pistol'],
    ['mp5', 'MP5', 'Владение MP5'],
    ['ak47', 'AK47', 'Владение AK47'],
    ['m4', 'M4', 'Владение M4'],
    ['sniperRifle', 'Sniper Rifle', 'Владение Sniper Rifle'],
    ['sawnoff', 'Sawnoff', 'Владение Sawnoff'],
    ['combatSg', 'Combat SG', 'Владение Combat SG'],
    ['uzi', 'UZI', 'Владение UZI'],
  ];

  const visibleSkills = skillsExpanded ? allSkills : allSkills.slice(0, 3);
  list.innerHTML = visibleSkills.map(([key, title, subtitle]) => `
    <div class="prof-skill">
      <div class="prof-skill-info">
        <b>${title}</b>
        <span>${subtitle}</span>
      </div>
      <strong class="prof-skill-val">${Number(skills[key] || 0)}</strong>
    </div>
  `).join('');

  const parent = list.parentElement;
  parent?.querySelectorAll('.prof-more').forEach((button) => button.remove());

  if (allSkills.length > 3) {
    const label = skillsExpanded ? 'Свернуть' : 'Показать все';
    const toggleButton = document.createElement('button');
    toggleButton.type = 'button';
    toggleButton.className = 'prof-more';
    toggleButton.id = 'profileToggleSkills';
    toggleButton.textContent = label;
    toggleButton.addEventListener('click', () => {
      if (skillsExpanded) {
        skillsExpanded = false;
        updateExpandState();
        renderSkills(currentSkills);
        return;
      }

      openListModal({
        title: 'Навыки',
        items: allSkills.map(([key, title, subtitle]) => `
          <div class="prof-skill prof-modal-skill">
            <div class="prof-skill-info">
              <b>${title}</b>
              <span>${subtitle}</span>
            </div>
            <strong class="prof-skill-val">${Number(skills[key] || 0)}</strong>
          </div>
        `),
        closeOnBackdrop: true,
      });
    });
    list.after(toggleButton);
  }
  updateExpandState();
}

async function loadCars(data) {
  if (!data.id) return;

  try {
    const response = await fetch(`/api/account/${data.id}/cars`);
    const result = await response.json();

    if (!response.ok) return;

    account.cars = result.cars || [];
    localStorage.setItem('hamsterAccount', JSON.stringify(account));
    renderCars(account.cars);
  } catch (_error) {
    renderCars(data.cars || []);
  }
}

async function loadFamily(data) {
  if (!data.id) return;

  try {
    const response = await fetch(`/api/account/${data.id}/family`);
    const result = await response.json();

    if (!response.ok) return;

    account.family = result.family;
    account.familyRank = result.familyRank;
    account.familyName = result.familyName;
    account.familyRankName = result.familyRankName;
    localStorage.setItem('hamsterAccount', JSON.stringify(account));

    setText('characterFamily', normalizeText(account.familyName, 'Не состоит'));
    setText('characterFamilyCard', normalizeText(account.familyName, 'Не состоит'));
    setText('characterFamilyRank', familyRankLabel(account));
  } catch (_error) {
    setText('characterFamily', normalizeText(data.familyName, 'Не состоит'));
    setText('characterFamilyCard', normalizeText(data.familyName, 'Не состоит'));
    setText('characterFamilyRank', familyRankLabel(data));
  }
}

async function loadVehicleNames() {
  try {
    const response = await fetch('assets/data/vehicle-names.txt');
    const text = await response.text();

    vehicleNames = parseVehicleNames(text);
    renderCars(account.cars || []);
  } catch (_error) {
    vehicleNames = fallbackVehicleNames();
  }
}

function parseVehicleNames(text) {
  const names = {};

  text.split(/\r?\n/).forEach((line) => {
    const match = line.trim().match(/^(\d+)\s+(.+)$/);
    if (!match) return;

    names[Number(match[1])] = match[2].trim();
  });

  return names;
}

function vehicleName(id) {
  return vehicleNames[Number(id)] || fallbackVehicleNames()[Number(id)] || `Транспорт #${id || 0}`;
}

function carPlate(car) {
  const number = normalizeText(car.number, '');
  const region = normalizeText(car.region, '');
  const cleanNumber = number === '---' || number === '0' ? '' : number;
  const cleanRegion = region === '---' || region === '0' ? '' : region;

  if (!cleanNumber) return 'Отсутствует';

  return cleanRegion ? `${cleanNumber} ${cleanRegion}` : cleanNumber;
}

function fallbackVehicleNames() {
  return {
    401: 'ИЖ 2712',
    612: 'BMW M5 E60',
    15107: 'BMW M5 F90',
    15138: 'BMW 1000R',
    15175: 'BMW M5 F90 CS',
    15622: 'BMW M3 F80 CS',
    15636: 'BMW M3 G80 CS',
    17446: 'Cadilac Escalade V T1XX',
    17447: 'BMW M3 E46 Drift',
  };
}

function setText(id, value) {
  const element = document.getElementById(id);
  if (element) element.textContent = value;
}

function setHealth(value) {
  const health = Math.min(100, Math.max(0, Number(value || 0)));
  const bar = document.getElementById('profileHealthBar');

  if (bar) bar.style.width = `${health}%`;
  setText('profileHealthText', `${health} / 100`);
}

function formatMoney(value) {
  return `${Number(value || 0).toLocaleString('ru-RU')} ₽`;
}

function normalizeText(value, fallback) {
  const text = String(value || '').trim();
  if (!text || text.toLowerCase() === 'none' || text.toLowerCase() === 'null') {
    return fallback;
  }

  return text;
}

function formatDate(value) {
  if (!value) return 'Нет данных';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return String(value);
  return date.toLocaleString('ru-RU', { dateStyle: 'short', timeStyle: 'short' });
}

function adminLabel(data) {
  if (Number(data.admin) > 0) return `Админ ${data.admin} ур.`;
  if (Number(data.yadmin) > 0) return `YouTube ${data.yadmin} ур.`;
  return 'Нет';
}

function familyRankLabel(data) {
  if (!Number(data.family || 0)) return 'Нет';

  const rankName = normalizeText(data.familyRankName, '');
  const rank = Number(data.familyRank || 0);

  if (rankName && rank > 0) return `${rankName} (${rank})`;
  if (rank > 0) return `${rank}`;
  return 'Не указан';
}

function jobLabel(value) {
  const jobs = {
    0: 'Без работы',
    1: 'Таксист',
    2: 'Механик',
    3: 'Дальнобойщик',
    4: 'Полицейский',
    5: 'Медик',
    6: 'Военный',
  };

  return jobs[Number(value)] || `Работа #${value || 0}`;
}
