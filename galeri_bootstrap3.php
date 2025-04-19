<?php include ('koneksi.php'); ?>

<?php
    //Ambil tanggal dari parameter GET (jika ada)
    $tanggal = $_GET['tanggal'] ?? null;
            
    //Query dasar 
    $sql = "SELECT * FROM gambar_thumbnail";
            
    // Jika ada filter tanggal, tambahkan kondisi
    if ($tanggal) {
        $sql .= " WHERE DATE(uploaded_at)='$tanggal'";
    }
            
    $sql .= " ORDER BY uploaded_at DESC";
    $result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Galeri Gambar Responsive</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" type="text/css" href="bootstrap-5.3.3-dist/css/bootstrap.min.css">
	<script src="bootstrap-5.3.3-dist\jquery\jquery-3.3.1.js"></script>
</head>
<body>
    <div class="container py-5">
        <h2 class="text-center mb-4">Galeri Gambar</h2>
        <!-- Filter berdasarkan tanggal -->
         <form method="GET" class="row g-3 mb-4 justify-content-center">
            <div class="col-md-4">
                <!-- input type="date" name="tanggal" class="form-control" value="=$_GET['tanggal'] ??" ?> -->
                <input type="date" name="tanggal" class="form-control" value="<?=htmlspecialchars($tanggal)?>">
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-success">Filter Tanggal</button>
                <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="btn btn-secondary">Reset</a>
            </div>
         </form>

        <div class="row g-4">
            <?php
                if($tanggal):
                    echo "<p class='text-muted text-center'>Menampilkan gambar yang diunggah pada tanggal: <strong>". htmlspecialchars($tanggal). "</strong></p>'";
                endif;

                // Tambahkan logika pagination sebelum query
                // Pagination setup
                $page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
                $limit = 2;
                $offset = ($page - 1 ) * $limit;	

                // Hitung total gambar
                try {
                    if ($tanggal) {
                        $count_stmt = $conn->prepare("SELECT COUNT(*) FROM gambar_thumbnail WHERE DATE(uploaded_at) = ?");
                        $count_stmt->bind_param("s", $tanggal);
                    } else {
                        $count_stmt = $conn->prepare("SELECT COUNT(*) FROM gambar_thumbnail");
                    }
                    
                    $count_stmt->execute();
                    $total_result = $count_stmt->get_result()->fetch_row()[0];
                    $total_pages = ceil($total_result / $limit);
                    
                    // Prepare main query with pagination
                    if ($tanggal) {
                        $stmt = $conn->prepare("SELECT * FROM gambar_thumbnail WHERE DATE(uploaded_at) = ? ORDER BY uploaded_at DESC LIMIT ? OFFSET ?");
                        $stmt->bind_param("sii", $tanggal, $limit, $offset);
                    } else {
                        $stmt = $conn->prepare("SELECT * FROM gambar_thumbnail ORDER BY uploaded_at DESC LIMIT ? OFFSET ?");
                        $stmt->bind_param("ii", $limit, $offset);
                    }
                    
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                } catch (Exception $e) {
                    error_log("Database error: " . $e->getMessage());
                    $error_message = "An error occurred while fetching the data.";
                }
            ?>
            

            <!-- Setelah galeri, tambahkan Pagination  -->
            <nav aria-label="Page Navigation">
                <ul class="pagination justify-content-center mt-4">
                    <?php if ($total_pages > 0): ?>
                    <nav aria-label="Page Navigation">
                        <ul class="pagination justify-content-center mt-4">
                            <?php
                            
                            $params = $_GET;
                            unset($params['page']);
                            $base_url = '?' . http_build_query($params);
                            
                            // Previous button
                            if ($page > 1):
                                $prev = $base_url . '&page=' . ($page - 1);
                                echo "<li class='page-item'>
                                        <a class='page-link' href='" . htmlspecialchars($prev) . "'>&laquo;</a>
                                      </li>";
                            endif;
                            
                            // Page numbers
                            for ($i = 1; $i <= $total_pages; $i++):
                                $page_url = $base_url . '&page=' . $i;
                                echo "<li class='page-item " . ($i == $page ? 'active' : '') . "'>
                                        <a class='page-link' href='" . htmlspecialchars($page_url) . "'>" . $i . "</a>
                                      </li>";
                            endfor;
                            
                            // Next button
                            if ($page < $total_pages):
                                $next = $base_url . '&page=' . ($page + 1);
                                echo "<li class='page-item'>
                                        <a class='page-link' href='" . htmlspecialchars($next) . "'>&raquo;</a>
                                      </li>";
                            endif;
                            ?>
                        </ul>
                    </nav>
                <?php endif; ?>
                </ul>
            </nav>
            
        <?php 
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $id_thumbnail = htmlspecialchars($row['id_thumbnail'], ENT_QUOTES, 'UTF-8');
                    $thumbpath = htmlspecialchars($row['thumbpath'], ENT_QUOTES, 'UTF-8');
                    $filepath = htmlspecialchars($row['filepath'], ENT_QUOTES, 'UTF-8');
                    $width = htmlspecialchars($row['width'], ENT_QUOTES, 'UTF-8');
                    $height = htmlspecialchars($row['height'], ENT_QUOTES, 'UTF-8');

                    echo"
                    <div class='col-12 col-sm-6 col-md-4 col-lg-3'>
                        <div class='card shadow-sm h-100'>
                            <img src='{$row['thumbpath']}' class='card-img-top img-thumbnail' alt='Thumbnail' loading='lazy' data-bs-toggle='modal' data-bs-target='#modal{$row['id_thumbnail']}'>
                            
                            <div class='card-body'>
                            <p class='card-text'><strong>Ukuran: </strong>{$row['width']}x{$row['height']}</p>
                            <a href='{$row['filepath']}' class='btn btn-sm btn-primary' target='_blank'>Lihat Asli</a>
                            
                            <form action='hapus.php' method='POST' onsubmit='return confirm(\"Yakin ingin menghapus gambar ini?\")'>
                                <input type='hidden' name='id' value='{$row['id_thumbnail']}'>
                                <input type='hidden' name='filepath' value='{$row['filepath']}'>
                                <input type='hidden' name='thumbpath' value='{$row['thumbpath']}'>
                                <button type='submit' class='btn btn-sm btn-danger mt-2'>Hapus</button>
                            </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Modal -->
                    <div class='modal fade' id='modal{$row['id_thumbnail']}' tabindex='-1'>
                    <div class='modal-dialog modal-dialog-centered modal-lg'>
                        <div class='modal-content'>
                        <div class='modal-body p-0'>
                            <img src='{$row['filepath']}' class='img-fluid w-100' alt='Full Image'>
                        </div>
                        </div>
                    </div>
                    </div>
                    ";   
                }
            }
            else {
                echo "<div class='alert alert-danger justify-content-center'><p class='text-center'>Gambar belum diunggah :(</p></div>";
            }
            if (isset($stmt)) $stmt->close();
            if (isset($count_stmt)) $count_stmt->close();
            $conn->close();
        ?>
        </div>
    </div>
    <script src="bootstrap-5.3.3-dist\js\bootstrap.bundle.min.js"></script>
</body>
</html>