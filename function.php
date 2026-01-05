<?php
session_start();

$conn = mysqli_connect("localhost", "root", "", "stock_barang");

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// tambah barang
if (isset($_POST['addnewbarang'])) {

    $namabarang = $_POST['namabarang'];
    $deskripsi  = $_POST['deskripsi'];
    $stock      = $_POST['stock'];

    $query = "INSERT INTO stock (namabarang, deskripsi, stock)
              VALUES ('$namabarang', '$deskripsi', '$stock')";

    $addtotable = mysqli_query($conn, $query);

    if ($addtotable) {
        header("Location: index.php");
        exit;
    } else {
        echo "Gagal menyimpan data: " . mysqli_error($conn);
    }
}

//Menambah barang masuk
if (isset($_POST['barangmasuk'])) {
    $barangnnya = $_POST['barangnya'];
    $penerima   = $_POST['penerima'];
    $qty        = $_POST['qty'];

    // Ambil stock sekarang
    $cekstockbarang = mysqli_query(
        $conn,
        "SELECT stock FROM stock WHERE idbarang='$barangnnya'"
    );
    $ambildatanya = mysqli_fetch_array($cekstockbarang);
    $stocksekarang = $ambildatanya['stock'];

    // Hitung stock baru
    $tambahkanstocksekarangdenganquantity = $stocksekarang + $qty;

    // Insert barang masuk
    $addtomasuk = mysqli_query(
        $conn,
        "INSERT INTO masuk (idbarang, keterangan, qty)
         VALUES ('$barangnnya', '$penerima', '$qty')"
    );

    // Update stock
    $updatestockmasuk = mysqli_query(
        $conn,
        "UPDATE stock
         SET stock='$tambahkanstocksekarangdenganquantity'
         WHERE idbarang='$barangnnya'"
    );

    if ($addtomasuk && $updatestockmasuk) {
        header("Location: masuk.php");
        exit;
    } else {
        echo "Gagal menyimpan data: " . mysqli_error($conn);
    }
}



// Menambah barang keluar
if (isset($_POST['addbarangkeluar'])) {
    $barangnnya = $_POST['barangnya'];
    $penerima   = $_POST['penerima'];
    $qty        = $_POST['qty'];

    // Ambil stock sekarang
    $cekstockbarang = mysqli_query(
        $conn,
        "SELECT stock FROM stock WHERE idbarang='$barangnnya'"
    );
    $ambildatanya   = mysqli_fetch_array($cekstockbarang);
    $stocksekarang = $ambildatanya['stock'];

    // Cek stock cukup atau tidak
    if ($stocksekarang < $qty) {
        echo "Stock tidak mencukupi";
        exit;
    }

    // Hitung stock baru
    $stockbaru = $stocksekarang - $qty;

    // Insert barang keluar
    $addtokeluar = mysqli_query(
        $conn,
        "INSERT INTO keluar (idbarang, penerima, qty)
         VALUES ('$barangnnya', '$penerima', '$qty')"
    );

    // Update stock
    $updatestokeluar = mysqli_query(
        $conn,
        "UPDATE stock
         SET stock='$stockbaru'
         WHERE idbarang='$barangnnya'"
    );

    if ($addtokeluar && $updatestokeluar) {
        header("Location: keluar.php");
        exit;
    } else {
        echo "Gagal menyimpan data: " . mysqli_error($conn);
    }
}

// Proses Update Barang
if(isset($_POST['updatebarang'])){
    $idb = $_POST['idb'];
    $namabarang = $_POST['namabarang'];
    $deskripsi = $_POST['deskripsi'];
    
    $update = mysqli_query($conn, "UPDATE stock SET 
                                    namabarang='$namabarang', 
                                    deskripsi='$deskripsi' 
                                    WHERE idbarang='$idb'");
    
    if($update){
        header("Location: index.php");
        exit();
    } else {
        echo '<script>
                alert("Gagal update: ' . mysqli_error($conn) . '");
              </script>';
    }
}

// Proses Delete Barang
if(isset($_POST['deletebarang'])){
    $idb = $_POST['idb'];
    
    $delete = mysqli_query($conn, "DELETE FROM stock WHERE idbarang='$idb'");
    
    if($delete){
        header("Location: index.php");
        exit();
    } else {
        echo '<script>
                alert("Gagal hapus: ' . mysqli_error($conn) . '");
              </script>';
    }
}

// UPDATE BARANG MASUK
if (isset($_POST['updatebarangmasuk'])) {

    $idm        = $_POST['idm'];        // idmasuk (ID unik untuk setiap transaksi masuk)
    $idb        = $_POST['idb'];        // idbarang
    $namabarang = $_POST['namabarang'];
    $keterangan = $_POST['keterangan'];
    $qty        = $_POST['qty'];

    // Cek apakah data diterima
    if(empty($idm) || empty($idb)) {
        echo '<script>alert("Data tidak lengkap!");</script>';
        exit;
    }

    // Ambil qty lama dari tabel masuk berdasarkan idmasuk
    $get_old_qty = mysqli_query($conn, "SELECT qty FROM masuk WHERE idmasuk = '$idm'");
    
    if(mysqli_num_rows($get_old_qty) > 0) {
        $data_old = mysqli_fetch_assoc($get_old_qty);
        $qty_lama = $data_old['qty'];
        
        // Hitung selisih qty
        $selisih = $qty - $qty_lama;

        // Update data di tabel MASUK berdasarkan idmasuk
        $update = mysqli_query($conn, "UPDATE masuk SET qty = '$qty', keterangan = '$keterangan' WHERE idmasuk = '$idm'");

        if($update) {
            // Update stok di tabel STOCK (tambah/kurang sesuai selisih)
            $updatestock = mysqli_query($conn, "UPDATE stock SET stock = stock + ($selisih) WHERE idbarang = '$idb'");
            
            if($updatestock) {
                header("Location: masuk.php");
                exit();
            } else {
                echo '<script>alert("Gagal update stok: ' . mysqli_error($conn) . '");</script>';
            }
        } else {
            echo '<script>alert("Gagal update masuk: ' . mysqli_error($conn) . '");</script>';
        }
    } else {
        echo '<script>alert("Data tidak ditemukan!");</script>';
    }
}


// DELETE BARANG MASUK
if (isset($_POST['deletebarangmasuk'])) {

    $idm = $_POST['idm'];  // idmasuk
    $idb = $_POST['idb'];  // idbarang

    // Cek apakah data diterima
    if(empty($idm) || empty($idb)) {
        echo '<script>alert("Data tidak lengkap!");</script>';
        exit;
    }

    // Ambil qty yang akan dihapus berdasarkan idmasuk
    $get_qty = mysqli_query($conn, "SELECT qty FROM masuk WHERE idmasuk = '$idm'");
    
    if(mysqli_num_rows($get_qty) > 0) {
        $data = mysqli_fetch_assoc($get_qty);
        $qty_hapus = $data['qty'];

        // Hapus dari tabel MASUK berdasarkan idmasuk
        $delete = mysqli_query($conn, "DELETE FROM masuk WHERE idmasuk = '$idm'");

        if($delete) {
            // Kurangi stok di tabel STOCK
            $updatestock = mysqli_query($conn, "UPDATE stock SET stock = stock - $qty_hapus WHERE idbarang = '$idb'");
            
            if($updatestock) {
                header("Location: masuk.php");
                exit();
            } else {
                echo '<script>alert("Gagal update stok: ' . mysqli_error($conn) . '");</script>';
            }
        } else {
            echo '<script>alert("Gagal hapus data: ' . mysqli_error($conn) . '");</script>';
        }
    } else {
        echo '<script>alert("Data tidak ditemukan!");</script>';
    }
}

// UPDATE BARANG KELUAR
if (isset($_POST['updatebarangkeluar'])) {

    $idk = $_POST['idk'];  // idkeluar (ID unik untuk setiap transaksi keluar)
    $idb = $_POST['idb'];  // idbarang
    $penerima = $_POST['penerima'];
    $qty = $_POST['qty'];

    // Cek apakah data diterima
    if(empty($idk) || empty($idb)) {
        echo '<script>alert("Data tidak lengkap!");</script>';
        exit;
    }

    // Ambil qty lama dari tabel keluar berdasarkan idkeluar
    $get_old_qty = mysqli_query($conn, "SELECT qty FROM keluar WHERE idkeluar = '$idk'");
    
    if(mysqli_num_rows($get_old_qty) > 0) {
        $data_old = mysqli_fetch_assoc($get_old_qty);
        $qty_lama = $data_old['qty'];
        
        // Hitung selisih qty (kebalikan dari masuk)
        $selisih = $qty_lama - $qty;  // Jika qty baru lebih kecil, stok bertambah

        // Update data di tabel KELUAR berdasarkan idkeluar
        $update = mysqli_query($conn, "UPDATE keluar SET qty = '$qty', penerima = '$penerima' WHERE idkeluar = '$idk'");

        if($update) {
            // Update stok di tabel STOCK (tambah stok karena qty keluar dikurangi)
            $updatestock = mysqli_query($conn, "UPDATE stock SET stock = stock + ($selisih) WHERE idbarang = '$idb'");
            
            if($updatestock) {
                header("Location: keluar.php");
                exit();
            } else {
                echo '<script>alert("Gagal update stok: ' . mysqli_error($conn) . '");</script>';
            }
        } else {
            echo '<script>alert("Gagal update keluar: ' . mysqli_error($conn) . '");</script>';
        }
    } else {
        echo '<script>alert("Data tidak ditemukan!");</script>';
    }
}


// DELETE BARANG KELUAR
if (isset($_POST['deletebarangkeluar'])) {

    $idk = $_POST['idk'];  // idkeluar
    $idb = $_POST['idb'];  // idbarang

    // Cek apakah data diterima
    if(empty($idk) || empty($idb)) {
        echo '<script>alert("Data tidak lengkap!");</script>';
        exit;
    }

    // Ambil qty yang akan dihapus berdasarkan idkeluar
    $get_qty = mysqli_query($conn, "SELECT qty FROM keluar WHERE idkeluar = '$idk'");
    
    if(mysqli_num_rows($get_qty) > 0) {
        $data = mysqli_fetch_assoc($get_qty);
        $qty_hapus = $data['qty'];

        // Hapus dari tabel KELUAR berdasarkan idkeluar
        $delete = mysqli_query($conn, "DELETE FROM keluar WHERE idkeluar = '$idk'");

        if($delete) {
            // Tambah stok kembali di tabel STOCK (karena barang keluar dibatalkan)
            $updatestock = mysqli_query($conn, "UPDATE stock SET stock = stock + $qty_hapus WHERE idbarang = '$idb'");
            
            if($updatestock) {
                header("Location: keluar.php");
                exit();
            } else {
                echo '<script>alert("Gagal update stok: ' . mysqli_error($conn) . '");</script>';
            }
        } else {
            echo '<script>alert("Gagal hapus data: ' . mysqli_error($conn) . '");</script>';
        }
    } else {
        echo '<script>alert("Data tidak ditemukan!");</script>';
    }
}


?>

