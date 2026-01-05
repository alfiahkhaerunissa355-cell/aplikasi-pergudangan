<?php
require 'function.php';
require 'cek.php';

// ========== DATA UNTUK LINE CHART - BARANG MASUK PER HARI (30 HARI TERAKHIR) ==========
$query_line = mysqli_query($conn, "
    SELECT DATE(tanggal) as tanggal, SUM(qty) as total_qty, COUNT(*) as jumlah_transaksi
    FROM masuk
    WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(tanggal)
    ORDER BY tanggal ASC
");

$tanggal_masuk = [];
$qty_masuk_harian = [];
$transaksi_count = [];

// Buat array 30 hari terakhir dengan nilai 0
for($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('d M', strtotime($date));
    $tanggal_masuk[] = $label;
    $qty_masuk_harian[$date] = 0;
    $transaksi_count[$date] = 0;
}

// Isi data dari database
mysqli_data_seek($query_line, 0);
while($row = mysqli_fetch_array($query_line)){
    $date = $row['tanggal'];
    if(isset($qty_masuk_harian[$date])) {
        $qty_masuk_harian[$date] = (int)$row['total_qty'];
        $transaksi_count[$date] = (int)$row['jumlah_transaksi'];
    }
}

$qty_values = array_values($qty_masuk_harian);
$transaksi_values = array_values($transaksi_count);

$tanggal_masuk_json = json_encode($tanggal_masuk);
$qty_masuk_json = json_encode($qty_values);
$transaksi_json = json_encode($transaksi_values);

// ========== DATA UNTUK BAR CHART - TOP 10 BARANG PALING SERING MASUK ==========
$query_bar = mysqli_query($conn, "
    SELECT s.namabarang, SUM(m.qty) as total_masuk
    FROM masuk m
    JOIN stock s ON m.idbarang = s.idbarang
    WHERE m.tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY m.idbarang
    ORDER BY total_masuk DESC
    LIMIT 10
");

$barang_masuk_names = [];
$barang_masuk_qty = [];

if(mysqli_num_rows($query_bar) > 0) {
    while($row = mysqli_fetch_array($query_bar)){
        $barang_masuk_names[] = $row['namabarang'];
        $barang_masuk_qty[] = (int)$row['total_masuk'];
    }
} else {
    $barang_masuk_names = ['Belum ada data'];
    $barang_masuk_qty = [0];
}

$barang_masuk_names_json = json_encode($barang_masuk_names);
$barang_masuk_qty_json = json_encode($barang_masuk_qty);
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Barang Masuk</title>
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
                        <h1 class="mt-4">Barang Masuk</h1>
                       
                        <!-- GRAFIK SECTION -->
                        <div class="row">
                            <!-- Line Chart - Pola Restock Harian -->
                            <div class="col-xl-8">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <i class="fas fa-chart-line me-1"></i>
                                        Pola Restock - Barang Masuk Per Hari (30 Hari Terakhir)
                                    </div>
                                    <div class="card-body">
                                        <canvas id="lineChartMasuk" width="100%" height="40"></canvas>
                                        <div class="mt-3 text-muted small">
                                            <i class="fas fa-info-circle"></i> Grafik ini menunjukkan kapan sering restock dan kapan sepi. Berguna untuk memprediksi pola pengadaan barang.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Bar Chart - Top 10 Barang Paling Sering Masuk -->
                            <div class="col-xl-4">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <i class="fas fa-chart-bar me-1"></i>
                                        Top 10 Barang Sering Masuk
                                    </div>
                                    <div class="card-body">
                                        <canvas id="barChartMasuk" width="100%" height="40"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TABEL RIWAYAT BARANG MASUK -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-table me-1"></i>
                                Riwayat Barang Masuk
                                <button type="button" class="btn btn-primary btn-sm float-end" data-bs-toggle="modal" data-bs-target="#myModal">
                                    <i class="fas fa-plus"></i> Tambah Barang Masuk
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
                                            <th>Keterangan</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                      <?php
                                        $ambilsemuadatamasuk = mysqli_query($conn, "SELECT * FROM masuk m, stock s where s.idbarang = m.idbarang ORDER BY m.tanggal DESC");
                                        $i = 1;
                                        while($data = mysqli_fetch_array($ambilsemuadatamasuk)){
                                            $idm = $data['idmasuk'];
                                            $idb = $data['idbarang'];
                                            $tanggal = date('d M Y H:i', strtotime($data['tanggal']));
                                            $namabarang = $data['namabarang'];
                                            $qty = $data['qty'];
                                            $keterangan = $data['keterangan'];
                                        ?>
                                        <tr>
                                            <td><?=$i++?></td>
                                            <td><?=$tanggal?></td>
                                            <td><?=$namabarang?></td>
                                            <td><span class="badge bg-success"><?=$qty?> unit</span></td>
                                            <td><?=$keterangan?></td>
                                            <td>
                                                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#edit<?=$idm?>">
                                                    Edit
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#delete<?=$idm?>">
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Modal Edit -->
                                        <div class="modal fade" id="edit<?=$idm?>">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h4 class="modal-title">Edit Barang Masuk</h4>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="post">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="idm" value="<?=$idm?>">
                                                            <input type="hidden" name="idb" value="<?=$idb?>">
                                                            
                                                            <label>Nama Barang</label>
                                                            <input type="text" name="namabarang" value="<?=$namabarang?>" class="form-control mb-3" readonly>
                                                            
                                                            <label>Keterangan</label>
                                                            <input type="text" name="keterangan" value="<?=$keterangan?>" class="form-control mb-3" required>

                                                            <label>Qty</label>
                                                            <input type="number" name="qty" value="<?=$qty?>" class="form-control mb-3" required>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="submit" class="btn btn-primary" name="updatebarangmasuk">Update</button>
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Modal Delete -->
                                        <div class="modal fade" id="delete<?=$idm?>">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h4 class="modal-title">Hapus Barang Masuk</h4>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="post">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="idm" value="<?=$idm?>">
                                                            <input type="hidden" name="idb" value="<?=$idb?>">
                                                            <p>Apakah Anda yakin ingin menghapus transaksi masuk barang <strong><?=$namabarang?></strong> sebanyak <strong><?=$qty?></strong> unit?</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="submit" class="btn btn-danger" name="deletebarangmasuk">Hapus</button>
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                        };
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
        
        <!-- SCRIPT LINE CHART - POLA RESTOCK -->
        <script>
            var tanggalMasuk = <?php echo $tanggal_masuk_json; ?>;
            var qtyMasuk = <?php echo $qty_masuk_json; ?>;
            var transaksiCount = <?php echo $transaksi_json; ?>;

            console.log('Tanggal:', tanggalMasuk);
            console.log('Qty Masuk:', qtyMasuk);

            var ctxLine = document.getElementById('lineChartMasuk');
            var lineChartMasuk = new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: tanggalMasuk,
                    datasets: [
                        {
                            label: "Total Barang Masuk (unit)",
                            lineTension: 0.3,
                            backgroundColor: "rgba(78, 115, 223, 0.05)",
                            borderColor: "rgba(78, 115, 223, 1)",
                            pointRadius: 3,
                            pointBackgroundColor: "rgba(78, 115, 223, 1)",
                            pointBorderColor: "rgba(255,255,255,0.8)",
                            pointHoverRadius: 5,
                            pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                            pointHitRadius: 50,
                            pointBorderWidth: 2,
                            data: qtyMasuk,
                            yAxisID: 'y-axis-1'
                        },
                        {
                            label: "Jumlah Transaksi",
                            lineTension: 0.3,
                            backgroundColor: "rgba(28, 200, 138, 0.05)",
                            borderColor: "rgba(28, 200, 138, 1)",
                            pointRadius: 3,
                            pointBackgroundColor: "rgba(28, 200, 138, 1)",
                            pointBorderColor: "rgba(255,255,255,0.8)",
                            pointHoverRadius: 5,
                            pointHoverBackgroundColor: "rgba(28, 200, 138, 1)",
                            pointHitRadius: 50,
                            pointBorderWidth: 2,
                            data: transaksiCount,
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

        <!-- SCRIPT BAR CHART - TOP BARANG MASUK -->
        <script>
            var barangMasukNames = <?php echo $barang_masuk_names_json; ?>;
            var barangMasukQty = <?php echo $barang_masuk_qty_json; ?>;

            var ctxBar = document.getElementById('barChartMasuk');
            var barChartMasuk = new Chart(ctxBar, {
                type: 'horizontalBar',
                data: {
                    labels: barangMasukNames,
                    datasets: [{
                        label: "Total Masuk",
                        backgroundColor: "rgba(78, 115, 223, 0.6)",
                        borderColor: "rgba(78, 115, 223, 1)",
                        data: barangMasukQty,
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
    
    <!-- Modal Tambah Barang Masuk -->
    <div class="modal fade" id="myModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Tambah Barang Masuk</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <label>Pilih Barang</label>
                        <select name="barangnya" class="form-control mb-3" required>
                            <option value="">-- Pilih Barang --</option>
                            <?php
                                $ambilsemuadatanya = mysqli_query($conn, "SELECT * FROM stock ORDER BY namabarang ASC");
                                while ($fetcharray = mysqli_fetch_array($ambilsemuadatanya)) {
                                    $namabarangnya = $fetcharray['namabarang'];
                                    $idbarangnya = $fetcharray['idbarang'];
                            ?>
                                <option value="<?=$idbarangnya;?>"><?=$namabarangnya;?></option>
                            <?php
                                }
                            ?>
                        </select>

                        <label>Jumlah (Qty)</label>
                        <input type="number" name="qty" class="form-control mb-3" placeholder="Masukkan jumlah" min="1" required>
                        
                        <label>Keterangan</label>
                        <input type="text" name="keterangan" class="form-control mb-3" placeholder="Contoh: Pembelian dari supplier" required>
                        
                        <button type="submit" class="btn btn-primary" name="barangmasuk">
                            <i class="fas fa-save"></i> Submit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</html>