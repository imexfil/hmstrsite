require('dotenv').config();

const crypto = require('crypto');
const express = require('express');
const mysql = require('mysql2/promise');
const app = express();
const port = Number(process.env.PORT || 3000);

const pool = mysql.createPool({
  host: process.env.DB_HOST,
  port: Number(process.env.DB_PORT || 3306),
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  database: process.env.DB_NAME,
  waitForConnections: true,
  connectionLimit: 5,
});

app.use(express.json());
app.use(express.static(__dirname));

app.get('/api/health', async (_req, res) => {
  try {
    await pool.query('SELECT 1');
    res.json({ ok: true });
  } catch (error) {
    res.status(500).json({ ok: false, message: 'Нет подключения к базе' });
  }
});

app.post('/api/account/login', async (req, res) => {
  const nickname = String(req.body.nickname || '').trim();
  const password = String(req.body.password || '');

  if (!nickname || !password) {
    return res.status(400).json({ message: 'Введите никнейм и пароль.' });
  }

  try {
    const [rows] = await pool.query(
      `SELECT
        id, name, password, password_new, email, skin, exp, level, money, bank,
        admin, yadmin, warn, suspect, online, donate, donate_current, donate_total,
        premium, premium_time, phone, phone_balance, driving_lic, weapon_lic,
        voennik, last_login, game_for_hour, game_for_day, rating, health,
        hunger, job, rub, rubs, family, family_rank, kills,
        skill_colt, skill_deagle, skill_sdpistol, skill_shotgun, skill_mp5,
        skill_ak47, skill_m4, skill_sniper_rifle, skill_sawnoff, skill_combat_sg,
        skill_micro_uzi
      FROM accounts
      WHERE LOWER(name) = LOWER(?)
      LIMIT 1`,
      [nickname],
    );

    const account = rows[0];
    if (!account || !passwordMatches(password, account)) {
      return res.status(401).json({ message: 'Никнейм или пароль не подошли.' });
    }

    const [cars] = await pool.query(
      `SELECT id, model_id, number, region, fuel, mileage, iznos
      FROM ownable_cars
      WHERE owner_id = ?
      ORDER BY id DESC
      LIMIT 12`,
      [account.id],
    );

    let familyInfo = null;
    if (Number(account.family) > 0) {
      const [families] = await pool.query(
        `SELECT id, name, rank1, rank2, rank3, rank4, rank5, rank6, rank7, rank8, rank9, rank10
        FROM family
        WHERE id = ?
        LIMIT 1`,
        [account.family],
      );
      familyInfo = families[0] || null;
    }

    res.json({ account: { ...toPublicAccount(account, familyInfo), cars: cars.map(toPublicCar) } });
  } catch (error) {
    console.error('Account login error:', error.message);
    res.status(500).json({ message: 'Не удалось получить данные аккаунта.' });
  }
});

app.get('/api/account/:id/cars', async (req, res) => {
  const accountId = Number(req.params.id || 0);

  if (!accountId) {
    return res.status(400).json({ message: 'Некорректный аккаунт.' });
  }

  try {
    const [cars] = await pool.query(
      `SELECT id, model_id, number, region, fuel, mileage, iznos
      FROM ownable_cars
      WHERE owner_id = ?
      ORDER BY id DESC
      LIMIT 12`,
      [accountId],
    );

    res.json({ cars: cars.map(toPublicCar) });
  } catch (error) {
    console.error('Account cars error:', error.message);
    res.status(500).json({ message: 'Не удалось получить транспорт.' });
  }
});

app.get('/api/account/:id/family', async (req, res) => {
  const accountId = Number(req.params.id || 0);

  if (!accountId) {
    return res.status(400).json({ message: 'Некорректный аккаунт.' });
  }

  try {
    const [rows] = await pool.query(
      `SELECT a.family, a.family_rank, f.name, f.rank1, f.rank2, f.rank3, f.rank4, f.rank5,
        f.rank6, f.rank7, f.rank8, f.rank9, f.rank10
      FROM accounts a
      LEFT JOIN family f ON f.id = a.family
      WHERE a.id = ?
      LIMIT 1`,
      [accountId],
    );

    const row = rows[0];

    if (!row) {
      return res.status(404).json({ message: 'Аккаунт не найден.' });
    }

    const familyRank = number(row.family_rank);

    res.json({
      family: number(row.family),
      familyRank,
      familyName: row.name ? String(row.name).trim() : null,
      familyRankName: row[`rank${familyRank}`] || null,
    });
  } catch (error) {
    console.error('Account family error:', error.message);
    res.status(500).json({ message: 'Не удалось получить семью аккаунта.' });
  }
});

app.listen(port, () => {
  console.log(`Hamster site is running: http://localhost:${port}`);
});

function passwordMatches(input, account) {
  const saved = [account.password, account.password_new].filter(Boolean).map(String);
  const variants = new Set([
    input,
    hash(input, 'md5'),
    hash(input, 'sha1'),
    hash(input, 'sha256'),
  ]);

  for (const value of Array.from(variants)) {
    variants.add(value.toUpperCase());
    variants.add(value.toLowerCase());
  }

  return saved.some((item) => variants.has(item));
}

function hash(value, algorithm) {
  return crypto.createHash(algorithm).update(value).digest('hex');
}

function toPublicAccount(account, familyInfo = null) {
  const familyRank = number(account.family_rank);

  return {
    id: account.id,
    name: account.name,
    email: account.email || 'Не указана',
    skin: number(account.skin),
    level: number(account.level),
    exp: number(account.exp),
    money: number(account.money),
    bank: number(account.bank),
    admin: number(account.admin),
    yadmin: number(account.yadmin),
    warn: number(account.warn),
    suspect: number(account.suspect),
    online: Boolean(Number(account.online || 0)),
    donate: number(account.donate),
    donateCurrent: number(account.donate_current),
    donateTotal: number(account.donate_total),
    premium: number(account.premium),
    premiumTime: account.premium_time,
    phone: account.phone || null,
    phoneBalance: number(account.phone_balance),
    drivingLic: number(account.driving_lic),
    weaponLic: number(account.weapon_lic),
    voennik: number(account.voennik),
    lastLogin: account.last_login,
    gameForHour: number(account.game_for_hour),
    gameForDay: number(account.game_for_day),
    rating: number(account.rating),
    health: number(account.health),
    hunger: number(account.hunger),
    job: number(account.job),
    rub: number(account.rub || account.rubs),
    family: number(account.family),
    familyRank,
    familyName: familyInfo ? String(familyInfo.name || '').trim() : null,
    familyRankName: familyInfo ? familyInfo[`rank${familyRank}`] || null : null,
    kills: number(account.kills),
    skills: {
      colt: number(account.skill_colt),
      deagle: number(account.skill_deagle),
      sdPistol: number(account.skill_sdpistol),
      shotgun: number(account.skill_shotgun),
      mp5: number(account.skill_mp5),
      ak47: number(account.skill_ak47),
      m4: number(account.skill_m4),
      sniperRifle: number(account.skill_sniper_rifle),
      sawnoff: number(account.skill_sawnoff),
      combatSg: number(account.skill_combat_sg),
      uzi: number(account.skill_micro_uzi),
    },
  };
}

function number(value) {
  return Number(value || 0);
}

function toPublicCar(car) {
  return {
    id: car.id,
    modelId: number(car.model_id),
    number: car.number || null,
    region: car.region || null,
    fuel: number(car.fuel),
    mileage: number(car.mileage),
    wear: number(car.iznos),
  };
}

function maskEmail(email) {
  if (!email || !String(email).includes('@')) return 'Не указан';

  const [name, domain] = String(email).split('@');
  const start = name.slice(0, 2);
  const end = name.length > 4 ? name.slice(-1) : '';
  return `${start}${'*'.repeat(Math.max(name.length - 3, 2))}${end}@${domain}`;
}
