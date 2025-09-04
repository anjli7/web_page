<?php
include 'include/header.php';
include 'php/db.php';

// Check if ID is provided in the URL
if(isset($_GET['id']) && is_numeric($_GET['id'])) {
    $post_id = $_GET['id'];
    
    // Prepare and execute the query to fetch the specific blog post
    $query = "SELECT * FROM articles WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $post = $result->fetch_assoc();
        ?>
        <div class="container my-5">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <article class="blog-post">
                        <h1 class="mb-4"><?php echo htmlspecialchars($post['title']); ?></h1>
                        
                        <div class="mb-4 text-center">
                            <img src="../application-system/uploads/<?php echo htmlspecialchars($post['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($post['title']); ?>" 
                                 class="img-fluid rounded" 
                                 style="max-height: 450px; width: auto; max-width: 100%; object-fit: cover;">
                        </div>
                        
                        <div class="post-meta mb-4">
                            <span class="text-muted">
                                <i class="far fa-calendar-alt me-2"></i>
                                <?php echo date('F d, Y', strtotime($post['created_at'])); ?>
                            </span>
                        </div>
                        
                        <div class="post-content">
                            <?php echo nl2br(htmlspecialchars($post['description'])); ?>
                        </div>
                        
                        <?php if(!empty($post['link'])): ?>
                       
                        <?php endif; ?>
                    </article>
                    
                    <div class="mt-5">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Blog
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    } else {
        // No post found with the given ID
        echo '<div class="container my-5">
                <div class="alert alert-danger">
                    <h4 class="alert-heading">Post Not Found</h4>
                    <p>The requested blog post could not be found.</p>
                    <a href="index.php" class="btn btn-outline-secondary mt-2">
                        <i class="fas fa-home me-2"></i>Back to Home
                    </a>
                </div>
              </div>';
    }
} else {
    // No ID provided in the URL
    header("Location: index.php");
    exit();
}

include 'include/footer.php';
?>
