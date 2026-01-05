<?php
require 'function.php';
require 'cek.php';

// ========== DATA UNTUK PIE CHART - STOK PER KATEGORI ==========
$query_pie = mysqli_query($conn, "
    SELECT 
        CASE 
            WHEN stock = 0 THEN 'Habis'
            WHEN stock > 0 AND stock <= 10 THEN 'Stok Menipis'
            WHEN stock > 10 AND stock <= 50 THEN 'Stok Normal'
            ELSE 'Stok Melimpah'
        END as kategori_stok,
        COUNT(*) as jumlah_barang,
        SUM(stock) as total_stock
    FROM stock
    GROUP BY kategori_stok
    ORDER BY 
        CASE kategori_stok
            WHEN 'Habis' THEN 1
            WHEN 'Stok Menipis' THEN 2
            WHEN 'Stok Normal' THEN 3
            WHEN 'Stok Melimpah' THEN 4
        END
");

$kategori_stok = [];
$jumlah_per_kategori = [];
$warna_kategori = [];

$warna_map = [
    'Habis' => 'rgba(231, 74, 59, 0.8)',
    'Stok Menipis' => 'rgba(246, 194, 62, 0.8)',
    'Stok Normal' => 'rgba(78, 115, 223, 0.8)',
    'Stok Melimpah' => 'rgba(28, 200, 138, 0.8)'
];

while($row = mysqli_fetch_array($query_pie)){
    $kategori_stok[] = $row['kategori_stok'] . ' (' . $row['jumlah_barang'] . ' item)';
    $jumlah_per_kategori[] = (int)$row['total_stock'];
    $warna_kategori[] = $warna_map[$row['kategori_stok']];
}

$kategori_stok_json = json_encode($kategori_stok);
$jumlah_per_kategori_json = json_encode($jumlah_per_kategori);
$warna_kategori_json = json_encode($warna_kategori);

// ========== DATA UNTUK BAR CHART - TOP 10 STOK TERBANYAK ==========
$query_bar = mysqli_query($conn, "
    SELECT namabarang, stock 
    FROM stock 
    ORDER BY stock DESC 
    LIMIT 10
");

$barang_names = [];
$barang_stock = [];

if(mysqli_num_rows($query_bar) > 0) {
    while($row = mysqli_fetch_array($query_bar)){
        $barang_names[] = $row['namabarang'];
        $barang_stock[] = (int)$row['stock'];
    }
} else {
    $barang_names = ['Belum ada data'];
    $barang_stock = [0];
}

$barang_names_json = json_encode($barang_names);
$barang_stock_json = json_encode($barang_stock);

// ========== STATISTIK DASHBOARD ==========
$total_barang = mysqli_query($conn, "SELECT COUNT(*) as total FROM stock");
$total_items = mysqli_fetch_assoc($total_barang)['total'];

$total_stok = mysqli_query($conn, "SELECT SUM(stock) as total FROM stock");
$total_stock_value = mysqli_fetch_assoc($total_stok)['total'] ?? 0;

$stok_habis = mysqli_query($conn, "SELECT COUNT(*) as total FROM stock WHERE stock = 0");
$habis_count = mysqli_fetch_assoc($stok_habis)['total'];

$stok_menipis = mysqli_query($conn, "SELECT COUNT(*) as total FROM stock WHERE stock > 0 AND stock <= 10");
$menipis_count = mysqli_fetch_assoc($stok_menipis)['total'];
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <title>Stock Barang</title>
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
                        <h1 class="mt-4">Stock Barang</h1>
                        
                        <!-- STATISTIK CARDS -->
                        <div class="row">
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-primary text-white mb-4">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col">
                                                <div class="text-xs font-weight-bold text-uppercase mb-1">Total Item Barang</div>
                                                <div class="h5 mb-0 font-weight-bold"><?= number_format($total_items) ?> Items</div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-boxes fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-success text-white mb-4">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col">
                                                <div class="text-xs font-weight-bold text-uppercase mb-1">Total Stok</div>
                                                <div class="h5 mb-0 font-weight-bold"><?= number_format($total_stock_value) ?> Unit</div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-warehouse fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-danger text-white mb-4">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col">
                                                <div class="text-xs font-weight-bold text-uppercase mb-1">Stok Habis</div>
                                                <div class="h5 mb-0 font-weight-bold"><?= number_format($habis_count) ?> Items</div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-exclamation-triangle fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-warning text-white mb-4">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col">
                                                <div class="text-xs font-weight-bold text-uppercase mb-1">Stok Menipis</div>
                                                <div class="h5 mb-0 font-weight-bold"><?= number_format($menipis_count) ?> Items</div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-exclamation-circle fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                       
                        <!-- GRAFIK SECTION -->
                        <div class="row">
                            <!-- Pie Chart - Kondisi Stok -->
                            <div class="col-xl-4">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <i class="fas fa-chart-pie me-1"></i>
                                        Kondisi Stok Saat Ini
                                    </div>
                                    <div class="card-body">
                                        <canvas id="pieChartStock" width="100%" height="100"></canvas>
                                        <div class="mt-3 small text-muted">
                                            <ul class="list-unstyled">
                                                <li><span class="text-danger">●</span> Habis = 0 unit</li>
                                                <li><span class="text-warning">●</span> Menipis = 1-10 unit</li>
                                                <li><span class="text-primary">●</span> Normal = 11-50 unit</li>
                                                <li><span class="text-success">●</span> Melimpah = >50 unit</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Bar Chart - Top 10 Stok Terbanyak -->
                            <div class="col-xl-8">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <i class="fas fa-chart-bar me-1"></i>
                                        Top 10 Barang dengan Stok Terbanyak
                                    </div>
                                    <div class="card-body">
                                        <canvas id="myBarChart" width="100%" height="40"></canvas>
                                        <div class="mt-3 text-muted small">
                                            <i class="fas fa-info-circle"></i> Grafik ini menunjukkan barang mana yang mendominasi gudang. Berguna untuk optimasi ruang penyimpanan.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- TABEL STOK DETAIL -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-table me-1"></i>
                                Detail Stok Barang
                                <button type="button" class="btn btn-primary btn-sm float-end" data-bs-toggle="modal" data-bs-target="#myModal">
                                    <i class="fas fa-plus"></i> Tambah Barang
                                </button>
                            </div>
                            <div class="card-body">
                                <table id="datatablesSimple">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Barang</th>
                                            <th>Deskripsi</th>
                                            <th>Stock</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                            $ambilsemuadatastock = mysqli_query($conn, "select * from stock ORDER BY stock ASC");
                                            $i = 1;
                                            while($data=mysqli_fetch_array($ambilsemuadatastock)){
                                                $idb = $data['idbarang'];
                                                $namabarang = $data['namabarang'];
                                                $deskripsi = $data['deskripsi'];
                                                $stock = $data['stock'];
                                                
                                                // Tentukan status dan badge color
                                                if($stock == 0) {
                                                    $status = 'Habis';
                                                    $badge = 'danger';
                                                } elseif($stock <= 10) {
                                                    $status = 'Menipis';
                                                    $badge = 'warning';
                                                } elseif($stock <= 50) {
                                                    $status = 'Normal';
                                                    $badge = 'primary';
                                                } else {
                                                    $status = 'Melimpah';
                                                    $badge = 'success';
                                                }
                                        ?>
                                        <tr>
                                            <td><?=$i++?></td>
                                            <td><strong><?=$namabarang?></strong></td>
                                            <td><?=$deskripsi?></td>
                                            <td><span class="badge bg-secondary"><?=$stock?> unit</span></td>
                                            <td><span class="badge bg-<?=$badge?>"><?=$status?></span></td>
                                            <td>
                                                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#edit<?=$idb?>">
                                                    Edit
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#delete<?=$idb?>">
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Modal Edit -->
                                        <div class="modal fade" id="edit<?=$idb?>">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h4 class="modal-title">Edit Barang</h4>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="post">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="idb" value="<?=$idb?>">
                                                            <label for="namabarang">Nama Barang</label>
                                                            <input type="text" name="namabarang" value="<?=$namabarang?>" class="form-control mb-3" required>
                                                            <label for="deskripsi">Deskripsi</label>
                                                            <input type="text" name="deskripsi" value="<?=$deskripsi?>" class="form-control mb-3" required>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="submit" class="btn btn-primary" name="updatebarang">Update</button>
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Modal Delete -->
                                        <div class="modal fade" id="delete<?=$idb?>">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h4 class="modal-title">Hapus Barang</h4>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="post">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="idb" value="<?=$idb?>">
                                                            <p>Apakah Anda yakin ingin menghapus barang <strong><?=$namabarang?></strong>?</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="submit" class="btn btn-danger" name="deletebarang">Hapus</button>
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
        
        <!-- SCRIPT PIE CHART - KONDISI STOK -->
        <script>
            var kategoriStok = <?php echo $kategori_stok_json; ?>;
            var jumlahPerKategori = <?php echo $jumlah_per_kategori_json; ?>;
            var warnaKategori = <?php echo $warna_kategori_json; ?>;

            console.log('Kategori Stok:', kategoriStok);
            console.log('Jumlah:', jumlahPerKategori);

            var ctxPie = document.getElementById('pieChartStock');
            var pieChartStock = new Chart(ctxPie, {
                type: 'doughnut',
                data: {
                    labels: kategoriStok,
                    datasets: [{
                        data: jumlahPerKategori,
                        backgroundColor: warnaKategori,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }],
                },
                options: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    },
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem, data) {
                                var label = data.labels[tooltipItem.index] || '';
                                var value = data.datasets[0].data[tooltipItem.index];
                                var total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                var percentage = Math.round((value / total) * 100);
                                return label + ': ' + value + ' unit (' + percentage + '%)';
                            }
                        }
                    }
                }
            });
        </script>
        
        <!-- SCRIPT BAR CHART - TOP 10 STOK -->
        <script>
            var barangNames = <?php echo $barang_names_json; ?>;
            var barangStock = <?php echo $barang_stock_json; ?>;

            console.log('Nama Barang:', barangNames);
            console.log('Stok Barang:', barangStock);

            var ctxBar = document.getElementById('myBarChart');
            var myBarChart = new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: barangNames,
                    datasets: [{
                        label: "Jumlah Stok",
                        backgroundColor: "rgba(2,117,216,0.6)",
                        borderColor: "rgba(2,117,216,1)",
                        data: barangStock,
                    }],
                },
                options: {
                    scales: {
                        xAxes: [{
                            gridLines: {
                                display: false
                            },
                            ticks: {
                                maxTicksLimit: 10
                            }
                        }],
                        yAxes: [{
                            ticks: {
                                min: 0,
                                maxTicksLimit: 5,
                                beginAtZero: true,
                                callback: function(value) {
                                    return value + ' unit';
                                }
                            },
                            gridLines: {
                                display: true
                            }
                        }],
                    },
                    legend: {
                        display: true
                    },
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return 'Stok: ' + tooltipItem.yLabel + ' unit';
                            }
                        }
                    }
                }
            });
        </script>
        
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
        <script src="js/datatables-simple-demo.js"></script>
    </body>
    
    <!-- Modal Tambah Barang -->
    <div class="modal fade" id="myModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Tambah Barang</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <label>Nama Barang</label>
                        <input type="text" name="namabarang" placeholder="Nama Barang" class="form-control mb-3" required>
                        
                        <label>Deskripsi</label>
                        <input type="text" name="deskripsi" placeholder="Deskripsi Barang" class="form-control mb-3" required>
                        
                        <label>Stok Awal</label>
                        <input type="number" name="stock" class="form-control mb-3" placeholder="Jumlah stok" min="0" required>
                        
                        <button type="submit" class="btn btn-primary" name="addnewbarang">
                            <i class="fas fa-save"></i> Submit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</html>