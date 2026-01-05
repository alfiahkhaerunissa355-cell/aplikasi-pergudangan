<?php
require 'function.php';
require 'cek.php';

// ========== DATA UNTUK LINE CHART - BARANG KELUAR PER HARI (30 HARI TERAKHIR) ==========
$query_line = mysqli_query($conn, "
    SELECT DATE(tanggal) as tanggal, SUM(qty) as total_qty, COUNT(*) as jumlah_transaksi
    FROM keluar
    WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(tanggal)
    ORDER BY tanggal ASC
");

$tanggal_keluar = [];
$qty_keluar_harian = [];
$transaksi_keluar_count = [];

// Buat array 30 hari terakhir dengan nilai 0
for($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('d M', strtotime($date));
    $tanggal_keluar[] = $label;
    $qty_keluar_harian[$date] = 0;
    $transaksi_keluar_count[$date] = 0;
}

// Isi data dari database
mysqli_data_seek($query_line, 0);
while($row = mysqli_fetch_array($query_line)){
    $date = $row['tanggal'];
    if(isset($qty_keluar_harian[$date])) {
        $qty_keluar_harian[$date] = (int)$row['total_qty'];
        $transaksi_keluar_count[$date] = (int)$row['jumlah_transaksi'];
    }
}

$qty_keluar_values = array_values($qty_keluar_harian);
$transaksi_keluar_values = array_values($transaksi_keluar_count);

$tanggal_keluar_json = json_encode($tanggal_keluar);
$qty_keluar_json = json_encode($qty_keluar_values);
$transaksi_keluar_json = json_encode($transaksi_keluar_values);

// ========== DATA UNTUK BAR CHART - TOP 10 BARANG PALING SERING KELUAR ==========
$query_bar = mysqli_query($conn, "
    SELECT s.namabarang, SUM(k.qty) as total_keluar, COUNT(k.idkeluar) as frekuensi
    FROM keluar k
    JOIN stock s ON k.idbarang = s.idbarang
    WHERE k.tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY k.idbarang
    ORDER BY total_keluar DESC
    LIMIT 10
");

$barang_keluar_names = [];
$barang_keluar_qty = [];

if(mysqli_num_rows($query_bar) > 0) {
    while($row = mysqli_fetch_array($query_bar)){
        $barang_keluar_names[] = $row['namabarang'];
        $barang_keluar_qty[] = (int)$row['total_keluar'];
    }
} else {
    $barang_keluar_names = ['Belum ada data'];
    $barang_keluar_qty = [0];
}

$barang_keluar_names_json = json_encode($barang_keluar_names);
$barang_keluar_qty_json = json_encode($barang_keluar_qty);

// ========== STATISTIK TAMBAHAN ==========
$total_keluar_bulan_ini = mysqli_query($conn, "
    SELECT SUM(qty) as total 
    FROM keluar 
    WHERE MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())
");
$total_keluar = mysqli_fetch_assoc($total_keluar_bulan_ini)['total'] ?? 0;

$total_transaksi_bulan_ini = mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM keluar 
    WHERE MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())
");
$total_transaksi = mysqli_fetch_assoc($total_transaksi_bulan_ini)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Barang Keluar</title>
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    </head>
    <body class="sb-nav-fixed">
        <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
            <a class="navbar-brand ps-3" href="index.php">Inventory APP</a>
            <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
        </nav>
        <div id="layoutSidenav">
            <div id="layoutSidenav_nav">
                <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                    <div class="sb-sidenav-menu">
                        <div class="nav">
                            <a class="nav-link" href="index.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                                Stock Barang
                            </a>
                             <a class="nav-link" href="masuk.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                                Barang Masuk
                            </a>
                             <a class="nav-link" href="keluar.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                                Barang Keluar
                            </a>
                             <a class="nav-link" href="logout.php">
                                Logout
                            </a>
                        </div>
                    </div>
                </nav>
            </div>
            <div id="layoutSidenav_content">
                <main>
                    <div class="container-fluid px-4">
                        <h1 class="mt-4">Barang Keluar</h1>
                        
                        <!-- STATISTIK CARDS -->
                        <div class="row">
                            <div class="col-xl-6 col-md-6">
                                <div class="card bg-danger text-white mb-4">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col">
                                                <div class="text-xs font-weight-bold text-uppercase mb-1">Total Keluar Bulan Ini</div>
                                                <div class="h5 mb-0 font-weight-bold"><?= number_format($total_keluar) ?> Unit</div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-box-open fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-6 col-md-6">
                                <div class="card bg-warning text-white mb-4">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col">
                                                <div class="text-xs font-weight-bold text-uppercase mb-1">Total Transaksi Keluar</div>
                                                <div class="h5 mb-0 font-weight-bold"><?= number_format($total_transaksi) ?> Transaksi</div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-clipboard-list fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                       
                        <!-- GRAFIK SECTION -->
                        <div class="row">
                            <!-- Line Chart - Pola Pengeluaran Harian -->
                            <div class="col-xl-8">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <i class="fas fa-chart-line me-1"></i>
                                        Pola Pengeluaran - Barang Keluar Per Hari (30 Hari Terakhir)
                                    </div>
                                    <div class="card-body">
                                        <canvas id="lineChartKeluar" width="100%" height="40"></canvas>
                                        <div class="mt-3 text-muted small">
                                            <i class="fas fa-info-circle"></i> Monitor pola pengeluaran untuk deteksi anomali. Lonjakan tiba-tiba bisa indikasi masalah atau permintaan tinggi.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Bar Chart - Top 10 Barang Paling Sering Keluar -->
                            <div class="col-xl-4">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <i class="fas fa-chart-bar me-1"></i>
                                        Top 10 Barang Sering Keluar
                                    </div>
                                    <div class="card-body">
                                        <canvas id="barChartKeluar" width="100%" height="40"></canvas>
                                        <div class="mt-2 text-muted small">
                                            <i class="fas fa-lightbulb"></i> Fast-moving items yang perlu monitoring stok ketat
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TABEL RIWAYAT BARANG KELUAR -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-table me-1"></i>
                                Riwayat Barang Keluar
                                <button type="button" class="btn btn-primary btn-sm float-end" data-bs-toggle="modal" data-bs-target="#myModal">
                                    <i class="fas fa-plus"></i> Tambah Barang Keluar
                                </button>
                            </div>
                            <div class="card-body">
                                <table id="datatablesSimple">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Tanggal</th>
                                            <th>Nama Barang</th>
                                            <th>Jumlah</th>
                                            <th>Penerima</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $ambilsemuadatakeluar = mysqli_query($conn, "
                                            SELECT k.*, s.namabarang 
                                            FROM keluar k 
                                            JOIN stock s ON k.idbarang = s.idbarang 
                                            ORDER BY k.tanggal DESC
                                        ");
                                        $i = 1;
                                        while($data = mysqli_fetch_array($ambilsemuadatakeluar)){
                                            $idk = $data['idkeluar'];
                                            $idb = $data['idbarang'];
                                            $tanggal = date('d M Y H:i', strtotime($data['tanggal']));
                                            $namabarang = $data['namabarang'];
                                            $qty = $data['qty'];
                                            $penerima = $data['penerima'];
                                        ?>
                                        <tr>
                                            <td><?=$i++?></td>
                                            <td><?=$tanggal?></td>
                                            <td><?=$namabarang?></td>
                                            <td><span class="badge bg-danger"><?=$qty?> unit</span></td>
                                            <td><?=$penerima?></td>
                                            <td>
                                                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#edit<?=$idk?>">
                                                    Edit
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#delete<?=$idk?>">
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Modal Edit -->
                                        <div class="modal fade" id="edit<?=$idk?>">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h4 class="modal-title">Edit Barang Keluar</h4>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="post">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="idk" value="<?=$idk?>">
                                                            <input type="hidden" name="idb" value="<?=$idb?>">
                                                            
                                                            <label>Nama Barang</label>
                                                            <input type="text" value="<?=$namabarang?>" class="form-control mb-3" readonly>
                                                            
                                                            <label>Penerima</label>
                                                            <input type="text" name="penerima" value="<?=$penerima?>" class="form-control mb-3" required>

                                                            <label>Qty</label>
                                                            <input type="number" name="qty" value="<?=$qty?>" class="form-control mb-3" required>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="submit" class="btn btn-primary" name="updatebarangkeluar">Update</button>
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Modal Delete -->
                                        <div class="modal fade" id="delete<?=$idk?>">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h4 class="modal-title">Hapus Barang Keluar</h4>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="post">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="idk" value="<?=$idk?>">
                                                            <input type="hidden" name="idb" value="<?=$idb?>">
                                                            <p>Apakah Anda yakin ingin menghapus transaksi keluar barang <strong><?=$namabarang?></strong> sebanyak <strong><?=$qty?></strong> unit untuk <strong><?=$penerima?></strong>?</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="submit" class="btn btn-danger" name="deletebarangkeluar">Hapus</button>
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </main>
                <footer class="py-4 bg-light mt-auto">
                    <div class="container-fluid px-4">
                        <div class="d-flex align-items-center justify-content-between small">
                            <div class="text-muted">Copyright &copy; Your Website 2023</div>
                        </div>
                    </div>
                </footer>
            </div>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="js/scripts.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
        
        <!-- SCRIPT LINE CHART - POLA PENGELUARAN -->
        <script>
            var tanggalKeluar = <?php echo $tanggal_keluar_json; ?>;
            var qtyKeluar = <?php echo $qty_keluar_json; ?>;
            var transaksiKeluarCount = <?php echo $transaksi_keluar_json; ?>;

            console.log('Tanggal Keluar:', tanggalKeluar);
            console.log('Qty Keluar:', qtyKeluar);

            var ctxLine = document.getElementById('lineChartKeluar');
            var lineChartKeluar = new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: tanggalKeluar,
                    datasets: [
                        {
                            label: "Total Barang Keluar (unit)",
                            lineTension: 0.3,
                            backgroundColor: "rgba(231, 74, 59, 0.05)",
                            borderColor: "rgba(231, 74, 59, 1)",
                            pointRadius: 3,
                            pointBackgroundColor: "rgba(231, 74, 59, 1)",
                            pointBorderColor: "rgba(255,255,255,0.8)",
                            pointHoverRadius: 5,
                            pointHoverBackgroundColor: "rgba(231, 74, 59, 1)",
                            pointHitRadius: 50,
                            pointBorderWidth: 2,
                            data: qtyKeluar,
                            yAxisID: 'y-axis-1'
                        },
                        {
                            label: "Jumlah Transaksi",
                            lineTension: 0.3,
                            backgroundColor: "rgba(246, 194, 62, 0.05)",
                            borderColor: "rgba(246, 194, 62, 1)",
                            pointRadius: 3,
                            pointBackgroundColor: "rgba(246, 194, 62, 1)",
                            pointBorderColor: "rgba(255,255,255,0.8)",
                            pointHoverRadius: 5,
                            pointHoverBackgroundColor: "rgba(246, 194, 62, 1)",
                            pointHitRadius: 50,
                            pointBorderWidth: 2,
                            data: transaksiKeluarCount,
                            yAxisID: 'y-axis-2'
                        }
                    ],
                },
                options: {
                    scales: {
                        xAxes: [{
                            gridLines: {
                                display: false
                            },
                            ticks: {
                                maxTicksLimit: 15
                            }
                        }],
                        yAxes: [
                            {
                                id: 'y-axis-1',
                                type: 'linear',
                                position: 'left',
                                ticks: {
                                    min: 0,
                                    beginAtZero: true,
                                    callback: function(value) {
                                        return value + ' unit';
                                    }
                                },
                                gridLines: {
                                    color: "rgba(0, 0, 0, .125)",
                                }
                            },
                            {
                                id: 'y-axis-2',
                                type: 'linear',
                                position: 'right',
                                ticks: {
                                    min: 0,
                                    beginAtZero: true,
                                    callback: function(value) {
                                        return value + ' x';
                                    }
                                },
                                gridLines: {
                                    display: false
                                }
                            }
                        ],
                    },
                    legend: {
                        display: true,
                        position: 'bottom'
                    },
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem, data) {
                                var label = data.datasets[tooltipItem.datasetIndex].label || '';
                                if(tooltipItem.datasetIndex === 0) {
                                    return label + ': ' + tooltipItem.yLabel + ' unit';
                                } else {
                                    return label + ': ' + tooltipItem.yLabel + ' transaksi';
                                }
                            }
                        }
                    }
                }
            });
        </script>

        <!-- SCRIPT BAR CHART - TOP BARANG KELUAR -->
        <script>
            var barangKeluarNames = <?php echo $barang_keluar_names_json; ?>;
            var barangKeluarQty = <?php echo $barang_keluar_qty_json; ?>;

            var ctxBar = document.getElementById('barChartKeluar');
            var barChartKeluar = new Chart(ctxBar, {
                type: 'horizontalBar',
                data: {
                    labels: barangKeluarNames,
                    datasets: [{
                        label: "Total Keluar",
                        backgroundColor: "rgba(231, 74, 59, 0.6)",
                        borderColor: "rgba(231, 74, 59, 1)",
                        data: barangKeluarQty,
                    }],
                },
                options: {
                    scales: {
                        xAxes: [{
                            ticks: {
                                min: 0,
                                beginAtZero: true,
                                callback: function(value) {
                                    return value + ' unit';
                                }
                            }
                        }]
                    },
                    legend: {
                        display: false
                    },
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return 'Total: ' + tooltipItem.xLabel + ' unit';
                            }
                        }
                    }
                }
            });
        </script>
        
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
        <script src="js/datatables-simple-demo.js"></script>
    </body>

    <!-- Modal Tambah Barang Keluar -->
    <div class="modal fade" id="myModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Tambah Barang Keluar</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <label>Pilih Barang</label>
                        <select name="barangnya" class="form-control mb-3" required>
                            <option value="">-- Pilih Barang --</option>
                            <?php
                                $ambilsemuadatanya = mysqli_query($conn, "SELECT * FROM stock WHERE stock > 0 ORDER BY namabarang ASC");
                                while ($fetcharray = mysqli_fetch_array($ambilsemuadatanya)) {
                                    $namabarangnya = $fetcharray['namabarang'];
                                    $idbarangnya = $fetcharray['idbarang'];
                                    $stocknya = $fetcharray['stock'];
                            ?>
                                <option value="<?=$idbarangnya;?>"><?=$namabarangnya;?> (Stok: <?=$stocknya?>)</option>
                            <?php
                                }
                            ?>
                        </select>

                        <label>Jumlah (Qty)</label>
                        <input type="number" name="qty" class="form-control mb-3" placeholder="Masukkan jumlah" min="1" required>
                        
                        <label>Penerima</label>
                        <input type="text" name="penerima" class="form-control mb-3" placeholder="Nama penerima / departemen" required>
                        
                        <button type="submit" class="btn btn-primary" name="addbarangkeluar">
                            <i class="fas fa-save"></i> Submit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</html>