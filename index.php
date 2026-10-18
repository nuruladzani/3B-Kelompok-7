<?php
date_default_timezone_set('Asia/Jakarta');
session_start();


if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'pengunjung'], true)) {
    $_SESSION['role'] = 'admin';
}
if (!isset($_SESSION['antrean']))         $_SESSION['antrean'] = [];          
if (!isset($_SESSION['riwayat']))         $_SESSION['riwayat'] = [];          
if (!isset($_SESSION['nomor_terakhir']))  $_SESSION['nomor_terakhir'] = 0;    
if (!isset($_SESSION['nomor_terpanggil'])) $_SESSION['nomor_terpanggil'] = 0;
if (!isset($_SESSION['nama_terpanggil']))  $_SESSION['nama_terpanggil'] = '';
if (!isset($_SESSION['keluhan_terpanggil'])) $_SESSION['keluhan_terpanggil'] = '';
if (!isset($_SESSION['mode_urut']))       $_SESSION['mode_urut'] = 'fifo';    

const DAFTAR_KELUHAN = ['Demam', 'Sakit Gigi', 'Sakit Lambung', 'Batuk/Pilek', 'Sakit Kepala', 'Lainnya'];


function tentukanRole($role) {
    return in_array($role, ['admin', 'pengunjung'], true) ? $role : 'pengunjung';
}


function ambilNomor($nama, $keluhan) {
    $_SESSION['nomor_terakhir']++;
    $nomor_baru = $_SESSION['nomor_terakhir'];
    $_SESSION['antrean'][] = [
        'nomor'   => $nomor_baru,
        'nama'    => $nama,
        'keluhan' => $keluhan,
        'waktu'   => date('H:i:s'),
    ];
    return $nomor_baru;
}

function resetAntrean() {
    $_SESSION['antrean'] = [];
    $_SESSION['riwayat'] = [];
    $_SESSION['nomor_terakhir'] = 0;
    $_SESSION['nomor_terpanggil'] = 0;
    $_SESSION['nama_terpanggil'] = '';
    $_SESSION['keluhan_terpanggil'] = '';
    $_SESSION['mode_urut'] = 'fifo';
}


function bandingNama($a, $b) {
    $c = strcasecmp($a['nama'], $b['nama']);
    return $c !== 0 ? $c : ($a['nomor'] <=> $b['nomor']);
}


function bandingKeluhan($a, $b) {
    $c = strcasecmp($a['keluhan'], $b['keluhan']);
    return $c !== 0 ? $c : ($a['nomor'] <=> $b['nomor']);
}


function bubbleSortNama(array $arr, &$perbandingan = 0, &$pertukaran = 0) {
    $n = count($arr);
    for ($i = 0; $i < $n - 1; $i++) {
        $tukar = false;
        for ($j = 0; $j < $n - $i - 1; $j++) {
            $perbandingan++;
            if (bandingNama($arr[$j], $arr[$j + 1]) > 0) {
                $temp = $arr[$j];
                $arr[$j] = $arr[$j + 1];
                $arr[$j + 1] = $temp;
                $pertukaran++;
                $tukar = true;
            }
        }
        if (!$tukar) break;
    }
    return $arr;
}


function selectionSortKeluhan(array $arr, &$perbandingan = 0, &$pertukaran = 0) {
    $n = count($arr);
    for ($i = 0; $i < $n - 1; $i++) {
        $min = $i;
        for ($j = $i + 1; $j < $n; $j++) {
            $perbandingan++;
            if (bandingKeluhan($arr[$j], $arr[$min]) < 0) {
                $min = $j;
            }
        }
        if ($min !== $i) {
            $temp = $arr[$i];
            $arr[$i] = $arr[$min];
            $arr[$min] = $temp;
            $pertukaran++;
        }
    }
    return $arr;
}


function linearSearchNomor(array $arr, $nomor, &$langkah = 0) {
    for ($i = 0; $i < count($arr); $i++) {
        $langkah++;
        if ($arr[$i]['nomor'] === $nomor) {
            return $i;
        }
    }
    return -1;
}


function binarySearchNama(array $arr, $nama, &$langkah = 0) {
    $kiri = 0;
    $kanan = count($arr) - 1;
    while ($kiri <= $kanan) {
        $langkah++;
        $tengah = intdiv($kiri + $kanan, 2);
        $cmp = strcasecmp($arr[$tengah]['nama'], $nama);
        if ($cmp === 0) {
            return $tengah;
        } elseif ($cmp < 0) {
            $kiri = $tengah + 1;
        } else {
            $kanan = $tengah - 1;
        }
    }
    return -1;
}

function formatWaktu($detik) {
    return number_format($detik * 1000000, 2) . ' Âµs';
}


$pesan = $pesan_panggil = $pesan_cari = $pesan_urut = $pesan_binary = '';
$hasil_cari = null;
$hasil_binary = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    
    if (isset($_POST['role'])) {
        $_SESSION['role'] = tentukanRole($_POST['role']);
    }

    $is_admin = $_SESSION['role'] === 'admin';

    
    if (isset($_POST['ambil'])) {
        $nama = trim($_POST['nama_pasien'] ?? '');
        if ($nama === '') $nama = 'Pasien';
        $keluhan = $_POST['keluhan'] ?? 'Lainnya';
        if (!in_array($keluhan, DAFTAR_KELUHAN, true)) $keluhan = 'Lainnya';

        $nomor_baru = ambilNomor($nama, $keluhan);
        $pesan = "Nomor antrean $nomor_baru berhasil diambil oleh $nama (keluhan: $keluhan).";
    }

    
    if (isset($_POST['reset'])) {
        if ($is_admin) {
            resetAntrean();
            $pesan = "Antrean berhasil direset.";
        } else {
            $pesan = "Hanya admin yang dapat mereset antrean.";
        }
    }

    
    if (isset($_POST['panggil'])) {
        if (!$is_admin) {
            $pesan_panggil = "Hanya admin yang dapat memanggil nomor antrean.";
        } elseif (!empty($_SESSION['antrean'])) {
            $terpanggil = array_shift($_SESSION['antrean']);   
            $_SESSION['riwayat'][] = $terpanggil;              
            $_SESSION['nomor_terpanggil'] = $terpanggil['nomor'];
            $_SESSION['nama_terpanggil'] = $terpanggil['nama'];
            $_SESSION['keluhan_terpanggil'] = $terpanggil['keluhan'];
            $pesan_panggil = "Memanggil nomor antrean: {$terpanggil['nomor']} - {$terpanggil['nama']}";
        } else {
            $pesan_panggil = "Tidak ada antrean yang tersisa.";
        }
    }

    
    if (isset($_POST['undo'])) {
        if (!$is_admin) {
            $pesan_panggil = "Hanya admin yang dapat membatalkan panggilan.";
        } elseif (!empty($_SESSION['riwayat'])) {
            $batal = array_pop($_SESSION['riwayat']);          
            array_unshift($_SESSION['antrean'], $batal);       
            $atas = end($_SESSION['riwayat']);                 
            if ($atas !== false) {
                $_SESSION['nomor_terpanggil'] = $atas['nomor'];
                $_SESSION['nama_terpanggil'] = $atas['nama'];
                $_SESSION['keluhan_terpanggil'] = $atas['keluhan'];
            } else {
                $_SESSION['nomor_terpanggil'] = 0;
                $_SESSION['nama_terpanggil'] = '';
                $_SESSION['keluhan_terpanggil'] = '';
            }
            $pesan_panggil = "Panggilan nomor {$batal['nomor']} - {$batal['nama']} dibatalkan, kembali ke antrean paling depan.";
        } else {
            $pesan_panggil = "Tidak ada riwayat panggilan untuk dibatalkan.";
        }
    }

    
    if (isset($_POST['urut'])) {
        $mode = $_POST['urut'];
        $_SESSION['mode_urut'] = in_array($mode, ['fifo', 'nama', 'keluhan'], true) ? $mode : 'fifo';
    }

    
    if (isset($_POST['cari'])) {
        $cari_nomor = (int) ($_POST['cari_nomor'] ?? 0);
        $langkah = 0;
        $mulai = microtime(true);
        $idx = linearSearchNomor($_SESSION['antrean'], $cari_nomor, $langkah);
        $waktu = microtime(true) - $mulai;

        if ($idx === -1) {
            $pesan_cari = "Nomor antrean $cari_nomor tidak ditemukan.";
        } else {
            $hasil_cari = $_SESSION['antrean'][$idx];
            $pesan_cari = "Ditemukan nomor antrean $cari_nomor.";
        }
    }

    
    if (isset($_POST['cari_binary'])) {
        $cari_nama = trim($_POST['cari_nama'] ?? '');
        if ($cari_nama === '') {
            $pesan_binary = "Masukkan nama yang ingin dicari.";
        } else {
            $mulai = microtime(true);
            $terurut = bubbleSortNama($_SESSION['antrean']);          
            $langkah = 0;
            $idx = binarySearchNama($terurut, $cari_nama, $langkah);  
            $waktu = microtime(true) - $mulai;

            if ($idx === -1) {
                $pesan_binary = "Nama \"$cari_nama\" tidak ditemukan.";
            } else {
                $hasil_binary = $terurut[$idx];
                $pesan_binary = "Nama \"$cari_nama\" ditemukan.";
            }
        }
    }
}

$is_admin = $_SESSION['role'] === 'admin';


$antrean_tampil = $_SESSION['antrean'];
$info_urut = '';
$mode = $_SESSION['mode_urut'];

if ($mode === 'nama') {
    $banding = 0; $tukar = 0;
    $mulai = microtime(true);
    $antrean_tampil = bubbleSortNama($_SESSION['antrean'], $banding, $tukar);
    $waktu = microtime(true) - $mulai;
    $info_urut = '';
} elseif ($mode === 'keluhan') {
    $banding = 0; $tukar = 0;
    $mulai = microtime(true);
    $antrean_tampil = selectionSortKeluhan($_SESSION['antrean'], $banding, $tukar);
    $waktu = microtime(true) - $mulai;
    $info_urut = '';
}

$riwayat_tampil = array_reverse($_SESSION['riwayat']); 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Antrean Klinik</title>   
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;600;800&display=swap');
        * { box-sizing: border-box; }

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

        h1 { margin: 0; color: #006064; font-weight: 800; letter-spacing: -1px; }
        h3 { color: #006064; }

        .role-form { display: flex; align-items: center; gap: 8px; }
        .role-form label { color: #4a6b6e; font-size: 14px; font-weight: 600; }

        .role-select {
            width: auto;
            padding: 8px 12px;
            border: 1px solid #b2dfdb;
            border-radius: 8px;
            background: #fff;
            color: #006064;
            font: inherit;
        }

        .card {
            margin-bottom: 20px;
            padding: 20px;
            border: 1px solid #e0f2f1;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 150, 136, 0.05);
        }

        .card-panggil { text-align: center; background: #fff5e6; }

        .pesan-panggil { margin-top: 10px; color: #d35400; font-size: 18px; font-weight: 600; }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.2s;
            color: white;
        }
        .btn:hover { transform: translateY(-2px); }

        .btn-ambil   { background: #00b894; }
        .btn-panggil { background: #e17055; }
        .btn-undo    { background: #fdcb6e; color: #2d3436; }
        .btn-cari    { background: #0984e3; }
        .btn-binary  { background: #6c5ce7; }
        .btn-reset   { background: #636e72; }
        .btn-bubble  { background: #00cec9; }
        .btn-select  { background: #fd79a8; }
        .btn-fifo    { background: #2d3436; }

        input, select.input-select {
            padding: 10px 14px;
            border: 2px solid #dfe6e9;
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
            transition: border-color 0.2s;
        }
        input:focus, select.input-select:focus { border-color: #00b894; outline: none; }

        .input-pasien, .input-cari { width: 60%; }
        .input-select { width: 60%; background: #fff; }
        .label-cari { display: block; }
        .form-spaced { margin-top: 10px; }
        .row-btn { display: flex; flex-wrap: wrap; gap: 8px; }

        .antrean-box {
            padding: 20px;
            border-left: 5px solid #00b894;
            border-radius: 14px;
            background: #f8f9fa;
        }

        .riwayat-box {
            padding: 20px;
            border-left: 5px solid #fdcb6e;
            border-radius: 14px;
            background: #fffdf5;
        }

        .item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        .item:last-child { border-bottom: none; }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            color: white;
            background: #00b894;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
        }

        .pesan { margin: 12px 0; padding: 14px; border-radius: 10px; font-weight: 500; }
        .pesan-ok   { border-left: 4px solid #2e7d32; color: #2e7d32; background: #e8f5e9; }
        .pesan-info { border-left: 4px solid #1565c0; color: #1565c0; background: #e3f2fd; }
        .pesan-prof { border-left: 4px solid #6c5ce7; color: #4834d4; background: #f1efff; font-size: 14px; }

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

        .nomor-terakhir { margin-top: 20px; color: #7f8c8d; font-size: 14px; }

        @media (max-width: 600px) {
            body { padding: 15px; }
            .container { padding: 22px; }
            .header { flex-direction: column; gap: 12px; }
            .input-pasien, .input-cari, .input-select { width: 100%; margin-bottom: 8px; }
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
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
</head>
<body>
<div class="container">
    <header class="header">
        <h1><i class='bx bx-clinic'></i> <i class='bx bxs-hospital'></i> Antrean Klinik</h1>
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
            <div class="nomor-besar"><?= (int) $_SESSION['nomor_terpanggil'] ?></div>
            <p class="pesan-panggil">
                Pasien: <?= htmlspecialchars($_SESSION['nama_terpanggil']) ?>
                (<?= htmlspecialchars($_SESSION['keluhan_terpanggil']) ?>)
            </p>
        </div>
    <?php endif; ?>

    <?php if ($pesan !== ''): ?><div class="pesan pesan-ok"><?= htmlspecialchars($pesan) ?></div><?php endif; ?>
    <?php if ($pesan_panggil !== ''): ?><div class="pesan pesan-info"><?= htmlspecialchars($pesan_panggil) ?></div><?php endif; ?>
    <?php if ($pesan_cari !== ''): ?><div class="pesan pesan-info"><?= htmlspecialchars($pesan_cari) ?></div><?php endif; ?>
    <?php if ($pesan_binary !== ''): ?><div class="pesan pesan-info"><?= htmlspecialchars($pesan_binary) ?></div><?php endif; ?>

    <!-- Ambil nomor -->
    <form method="post">
        <label for="nama_pasien">Nama Pasien:</label><br>
        <input type="text" id="nama_pasien" name="nama_pasien" placeholder="Masukkan nama" class="input-pasien"><br><br>
        <label for="keluhan">Keluhan:</label><br>
        <select id="keluhan" name="keluhan" class="input-select">
            <?php foreach (DAFTAR_KELUHAN as $k): ?>
                <option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($k) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="ambil" class="btn btn-ambil"><i class='bx bx-plus-circle'></i> Ambil Nomor Antrean</button>
    </form>

    <!-- Tombol admin -->
    <?php if ($is_admin): ?>
        <form method="post" class="form-spaced">
            <div class="row-btn">
                <button type="submit" name="panggil" class="btn btn-panggil" onclick="playTingTung(event);"><i class='bx bx-bell'></i> Panggil Nomor Berikutnya</button>
                <button type="submit" name="undo" class="btn btn-undo"><i class='bx bx-undo'></i> Undo Panggilan</button>
            </div>
            <div class="row-btn" style="margin-top: 8px;">
                <button type="submit" name="reset" class="btn btn-reset"><i class='bx bx-refresh'></i> Reset Antrean</button>
            </div>
        </form>
    <?php endif; ?>

    <!-- Linear Search -->
    <form method="post" class="form-spaced">
        <label for="cari-nomor" class="label-cari">Cari Nomor:</label>
        <input type="number" id="cari-nomor" name="cari_nomor" placeholder="Nomor antrean" class="input-cari">
        <button type="submit" name="cari" class="btn btn-cari"><i class='bx bx-search'></i> Cari</button>
    </form>

    <?php if ($hasil_cari): ?>
        <div class="pesan pesan-ok">
            Ditemukan: Nomor <?= (int) $hasil_cari['nomor'] ?> - <?= htmlspecialchars($hasil_cari['nama']) ?>
            (<?= htmlspecialchars($hasil_cari['keluhan']) ?>, <?= htmlspecialchars($hasil_cari['waktu']) ?>)
        </div>
    <?php endif; ?>

    <!-- Binary Search -->
    <form method="post" class="form-spaced">
        <label for="cari-nama" class="label-cari">Cari Nama:</label>
        <input type="text" id="cari-nama" name="cari_nama" placeholder="Nama pasien" class="input-cari">
        <button type="submit" name="cari_binary" class="btn btn-binary"><i class='bx bx-search'></i> Cari Nama</button>
    </form>

    <?php if ($hasil_binary): ?>
        <div class="pesan pesan-ok">
            Ditemukan: Nomor <?= (int) $hasil_binary['nomor'] ?> - <?= htmlspecialchars($hasil_binary['nama']) ?>
            (<?= htmlspecialchars($hasil_binary['keluhan']) ?>, <?= htmlspecialchars($hasil_binary['waktu']) ?>)
        </div>
    <?php endif; ?>

    <!-- Pilihan pengurutan tampilan -->
    <h3>Daftar Antrean Saat Ini</h3>
    <form method="post">
        <div class="row-btn">
            <button type="submit" name="urut" value="fifo" class="btn btn-fifo"><i class='bx bx-list-ol'></i> Urutkan Kedatangan</button>
            <button type="submit" name="urut" value="nama" class="btn btn-bubble"><i class='bx bx-sort-a-z'></i> Urutkan Abjad</button>
            <button type="submit" name="urut" value="keluhan" class="btn btn-select"><i class='bx bx-first-aid'></i> Urutkan Keluhan</button>
        </div>
    </form>


    <?php if ($info_urut !== ''): ?>
        <div class="pesan pesan-prof"><i class='bx bx-timer'></i> <?= htmlspecialchars($info_urut) ?></div>
    <?php endif; ?>

    <div class="antrean-box">
        <?php if (empty($antrean_tampil)): ?>
            <p>Tidak ada antrean.</p>
        <?php else: ?>
            <?php for ($i = 0; $i < count($antrean_tampil); $i++): $item = $antrean_tampil[$i]; ?>
                <div class="item">
                    <div>
                        <strong>Nomor: <?= (int) $item['nomor'] ?></strong> |
                        Nama: <?= htmlspecialchars($item['nama']) ?> |
                        Waktu: <?= htmlspecialchars($item['waktu']) ?>
                    </div>
                    <span class="badge"><?= htmlspecialchars($item['keluhan']) ?></span>
                </div>
            <?php endfor; ?>
        <?php endif; ?>
    </div>

    <!-- Stack riwayat -->
    <h3>Riwayat Panggilan</h3>
    <div class="riwayat-box">
        <?php if (empty($riwayat_tampil)): ?>
            <p>Belum ada riwayat panggilan.</p>
        <?php else: ?>
            <?php for ($i = 0; $i < count($riwayat_tampil); $i++): $r = $riwayat_tampil[$i]; ?>
                <div class="item">
                    <div>
                                                Nomor <?= (int) $r['nomor'] ?> - <?= htmlspecialchars($r['nama']) ?>
                    </div>
                    <span class="badge"><?= htmlspecialchars($r['keluhan']) ?></span>
                </div>
            <?php endfor; ?>
        <?php endif; ?>
    </div>

    <p class="nomor-terakhir">
        Nomor terakhir terpanggil: <strong><?= (int) $_SESSION['nomor_terpanggil'] ?></strong> |
        Nomor terakhir yang dibagikan: <strong><?= (int) $_SESSION['nomor_terakhir'] ?></strong>
    </p>
</div>
</body>
</html>

