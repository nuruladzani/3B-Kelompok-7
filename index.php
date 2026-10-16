<?php
//test
date_default_timezone_set('Asia/Jakarta');
session_start();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'pengunjung'], true)) {
    $_SESSION['role'] = 'admin';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role'])) {
    $role = $_POST['role'];
    $_SESSION['role'] = tentukanRole($role);
}

$is_admin = $_SESSION['role'] === 'admin';

// untuk inisialisasi queye antrean jika belum ada
if (!isset($_SESSION['antrean'])) {
    $_SESSION['antrean'] = [];
}

// untuk inisialisasi nomor antrean saat ini
if (!isset($_SESSION['nomor_terpanggil'])) {
    $_SESSION['nomor_terpanggil'] = 0;
}

if (!isset($_SESSION['nama_terpanggil'])) {
    $_SESSION['nama_terpanggil'] = '';
}

function tentukanRole($role) {
    $allowed = ['admin', 'pengunjung'];
    return in_array($role, $allowed, true) ? $role : 'pengunjung';
}

function ambilNomor($nama) {
    $nomor_baru = count($_SESSION['antrean']) + 1;
    $_SESSION['antrean'][] = [
        'nomor' => $nomor_baru,
        'nama' => $nama,
        'waktu' => date('H:i:s')
    ];
    return $nomor_baru;
}

function resetAntrean() {
    $_SESSION['antrean'] = [];
    $_SESSION['nomor_terpanggil'] = 0;
    $_SESSION['nama_terpanggil'] = '';
}

function bubbleSortAntrean(&$arr) {
    $n = count($arr);
    for ($i = 0; $i < $n - 1; $i++) {
        for ($j = 0; $j < $n - $i - 1; $j++) {
            if ($arr[$j]['nomor'] > $arr[$j + 1]['nomor']) {
                $temp = $arr[$j];
                $arr[$j] = $arr[$j + 1];
                $arr[$j + 1] = $temp;
            }
        }
    }
}

// ini buat ambil nomor antrean baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ambil'])) {
    $nama = trim($_POST['nama_pasien'] ?? 'Pasien');
    $nomor_baru = ambilNomor($nama);
    $pesan = "Nomor antrean $nomor_baru berhasil diambil oleh $nama.";
}

// Proses reset antrean
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset'])) {
    if ($is_admin) {
        resetAntrean();
        $pesan = "Antrean berhasil direset.";
    } else {
        $pesan = "Hanya admin yang dapat mereset antrean.";
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['panggil'])) {
    if (!$is_admin) {
        $pesan_panggil = "Hanya admin yang dapat memanggil nomor antrean.";
    } elseif (!empty($_SESSION['antrean'])) {
        // Dequeue: ambil dari depan array (FIFO)
        $terpanggil = array_shift($_SESSION['antrean']);
        $_SESSION['nomor_terpanggil'] = $terpanggil['nomor'];
        $_SESSION['nama_terpanggil'] = $terpanggil['nama'];
        $pesan_panggil = "Memanggil nomor antrean: {$terpanggil['nomor']} - {$terpanggil['nama']}";
    } else {
        $pesan_panggil = "Tidak ada antrean yang tersisa.";
    }
}

// (linear search) mencari nomor antrean tertentu
$hasil_cari = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cari'])) {
    $cari_nomor = (int) ($_POST['cari_nomor'] ?? 0);
    // Linear Search: iterasi satu per satu
    foreach ($_SESSION['antrean'] as $item) {
        if ($item['nomor'] === $cari_nomor) {
            $hasil_cari = $item;
            break;
        }
    }
    if ($hasil_cari === null) {
        $pesan_cari = "Nomor antrean $cari_nomor tidak ditemukan.";
    } else {
        $pesan_cari = "Ditemukan nomor antrean $cari_nomor.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Antrean Klinik</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;600;800&display=swap');
        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            padding: 30px;
            background: #e8f6f3;
            font-family: 'Inter', sans-serif;
        }

        .container {
            max-width: 800px;
            margin: auto;
            padding: 35px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
            backdrop-filter: blur(8px);
        }

        .header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 25px;
        }

        h1 {
            margin: 0;
            color: #006064;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .role-form {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .role-form label {
            color: #4a6b6e;
            font-size: 14px;
            font-weight: 600;
        }

        .role-select {
            width: auto;
            padding: 8px 12px;
            border: 1px solid #b2dfdb;
            border-radius: 8px;
            background: #fff;
            color: #006064;
            font: inherit;
        }

        .subtitle {
            margin-bottom: 25px;
            color: #4a6b6e;
            font-size: 15px;
        }

        .card {
            margin-bottom: 20px;
            padding: 20px;
            border: 1px solid #e0f2f1;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 150, 136, 0.05);
        }

        .card-panggil {
            text-align: center;
            background: #fff5e6;
        }

        .pesan-panggil {
            margin-top: 10px;
            color: #d35400;
            font-size: 18px;
            font-weight: 600;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-ambil {
            color: white;
            background: #00b894;
        }

        .btn-ambil:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 184, 148, 0.3);
        }

        .btn-panggil {
            color: white;
            background: #e17055;
        }

        .btn-panggil:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(225, 112, 85, 0.3);
        }

        .btn-cari {
            color: white;
            background: #0984e3;
        }

        .btn-reset {
            color: white;
            background: #636e72;
        }

        input {
            width: 60%;
            padding: 10px 14px;
            border: 2px solid #dfe6e9;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.2s;
        }

        input:focus {
            border-color: #00b894;
            outline: none;
        }

        .input-pasien,
        .input-cari {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .input-pasien {
            width: 60%;
        }

        .input-cari {
            width: 60%;
        }

        .label-cari {
            display: block;
        }

        .form-spaced {
            margin-top: 10px;
        }

        .antrean-box {
            padding: 20px;
            border-left: 5px solid #00b894;
            border-radius: 14px;
            background: #f8f9fa;
        }

        .item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }

        .item:last-child {
            border-bottom: none;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            color: white;
            background: #00b894;
            font-size: 13px;
            font-weight: 700;
        }

        .pesan {
            margin: 12px 0;
            padding: 14px;
            border-radius: 10px;
            font-weight: 500;
        }

        .pesan-ok {
            border-left: 4px solid #2e7d32;
            color: #2e7d32;
            background: #e8f5e9;
        }

        .pesan-info {
            border-left: 4px solid #1565c0;
            color: #1565c0;
            background: #e3f2fd;
        }

        .nomor-besar {
            color: #e17055;
            font-size: 72px;
            font-weight: 800;
            text-align: center;
            letter-spacing: -4px;
        }

        .label-kecil {
            color: #636e72;
            font-size: 13px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .nomor-terakhir {
            margin-top: 20px;
            color: #7f8c8d;
            font-size: 14px;
        }

        @media (max-width: 600px) {
            body {
                padding: 15px;
            }

            .container {
                padding: 22px;
            }

            .header {
                flex-direction: column;
                gap: 12px;
            }
        }
    </style>
    <script>
        async function playTingTung(event) {
            event.preventDefault();

            const button = event.currentTarget;
            const form = button.form;
            const audio = new Audio(new URL('assets/audio/Announcement%20sound%20effect.mp3', document.baseURI));
            audio.volume = 1.0;

            try {
                const playbackEnded = new Promise((resolve, reject) => {
                    audio.addEventListener('ended', resolve, { once: true });
                    audio.addEventListener('error', () => reject(audio.error || new Error('File audio tidak dapat dimuat.')), { once: true });
                });
                await audio.play();
                await playbackEnded;
            } catch (error) {
                console.error('Suara panggilan gagal diputar:', error);
                window.alert('Suara panggilan tidak dapat diputar. Pemanggilan antrean tetap dilanjutkan.');
            }

            form.requestSubmit(button);
        }
    </script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
<div class="container">
    <header class="header">
        <h1><i class='bx bx-clinic'></i> Antrean Klinik</h1>
        <form method="post" class="role-form">
            <label for="role">Role:</label>
            <select id="role" name="role" class="role-select" onchange="this.form.submit()">
                <option value="admin" <?= $_SESSION['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="pengunjung" <?= $_SESSION['role'] === 'pengunjung' ? 'selected' : '' ?>>Pengunjung</option>
            </select>
        </form>
    </header>

    <?php if ($_SESSION['nomor_terpanggil'] > 0): ?>
        <div class="card card-panggil">
            <p class="label-kecil">Nomor Terpanggil</p>
            <div class="nomor-besar"><?= $_SESSION['nomor_terpanggil'] ?></div>
            <p class="pesan-panggil">Pasien: <?= htmlspecialchars($_SESSION['nama_terpanggil']) ?></p>
        </div>
    <?php endif; ?>
    <?php if (!empty($pesan)): ?><div class="pesan pesan-ok"><?= htmlspecialchars($pesan) ?></div><?php endif; ?>
    <?php if (!empty($pesan_panggil)): ?><div class="pesan pesan-info"><?= htmlspecialchars($pesan_panggil) ?></div><?php endif; ?>
    <?php if (!empty($pesan_cari)): ?><div class="pesan pesan-info"><?= htmlspecialchars($pesan_cari) ?></div><?php endif; ?>

    <form method="post">
        <label>Nama Pasien:</label><br>
        <input type="text" name="nama_pasien" placeholder="Masukkan nama" class="input-pasien">
        <button type="submit" name="ambil" class="btn btn-ambil"><i class='bx bx-plus-circle'></i> Ambil Nomor Antrean</button>
    </form>

    <?php if ($is_admin): ?>
        <form method="post" class="form-spaced">
            <button type="submit" name="reset" class="btn btn-reset"><i class='bx bx-refresh'></i> Reset Antrean</button>
        </form>

        <form method="post" class="form-spaced">
            <button type="submit" name="panggil" class="btn btn-panggil" onclick="playTingTung(event);"><i class='bx bx-bell'></i> Panggil Nomor Berikutnya</button>
        </form>
    <?php endif; ?>

    <form method="post" class="form-spaced">
        <label for="cari-nomor" class="label-cari">Cari Nomor:</label>
        <input type="number" id="cari-nomor" name="cari_nomor" placeholder="Nomor antrean" class="input-cari">
        <button type="submit" name="cari" class="btn btn-cari"><i class='bx bx-search'></i> Cari</button>
    </form>

    <?php if ($hasil_cari): ?>
        <div class="pesan pesan-ok">
            Ditemukan: Nomor <?= $hasil_cari['nomor'] ?> - <?= htmlspecialchars($hasil_cari['nama']) ?> (<?= $hasil_cari['waktu'] ?>)
        </div>
    <?php endif; ?>

    <h3>Daftar Antrean Saat Ini</h3>
    <div class="antrean-box">
        <?php if (empty($_SESSION['antrean'])): ?>
            <p>Tidak ada antrean.</p>
        <?php else: ?>
            <?php
            $antrean = $_SESSION['antrean'];
            bubbleSortAntrean($antrean);
            for ($i = 0; $i < count($antrean); $i++) {
                $item = $antrean[$i];
            ?>
                <div class="item">
                    <strong>Nomor: <?= $item['nomor'] ?></strong> | Nama: <?= htmlspecialchars($item['nama']) ?> | Waktu: <?= $item['waktu'] ?>
                </div>
            <?php } ?>
        <?php endif; ?>
    </div>

    <p class="nomor-terakhir">
        Nomor terakhir terpanggil: <strong><?= $_SESSION['nomor_terpanggil'] ?></strong>
    </p>
</div>
</body>
</html>
