<?php
require_once 'includes/db.php';

// Fetch attractions
$stmt = $pdo->query("SELECT * FROM attractions WHERE is_active = 1");
$attractions = $stmt->fetchAll(PDO::FETCH_ASSOC);

require 'includes/header.php';
?>

<div class="hero hero-landing">
    <div class="hero-content animate-fade-in" style="text-align: center;">
        <h1 style="font-size: 4.5rem; font-weight: 800; margin-bottom: 1.5rem; line-height: 1.1; color: white;">Explore Your Next Adventure</h1>
        <p style="font-size: 1.5rem; max-width: 750px; margin: 0 auto; color: rgba(255,255,255,0.9);">Discover breathtaking tourist spots and book your experience in under 60 seconds.</p>
    </div>
</div>

<main class="container main-content-wrapper" style="margin-top: -2rem; position: relative; z-index: 10;">
    <div class="section-header" style="margin-bottom: 3rem;">
        <h2 class="page-title" style="font-size: 2.5rem;">Top Rated Spots</h2>
    </div>

    <div class="slot-grid" style="grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 2.5rem; margin-bottom: 8rem;">
        <?php foreach ($attractions as $spot): ?>
            <a href="booking.php?attraction_id=<?php echo htmlspecialchars($spot['id']); ?>" class="spot-card card animate-fade-in" style="padding: 0; display: block; text-decoration: none;">
                <div style="height: 280px; overflow: hidden; position: relative;">
                    <img src="<?php echo htmlspecialchars($spot['image_url']); ?>" alt="<?php echo htmlspecialchars($spot['name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <div style="position: absolute; top: 1.25rem; left: 1.25rem; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(8px); color: white; padding: 0.4rem 1rem; border-radius: 2rem; font-size: 0.8rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($spot['location']); ?>
                    </div>
                </div>
                <div style="padding: 2rem; display: flex; flex-direction: column; gap: 1rem; height: calc(100% - 280px);">
                    <h3 style="font-size: 1.75rem; color: var(--text-main); margin: 0;"><?php echo htmlspecialchars($spot['name']); ?></h3>
                    <p style="color: var(--text-muted); font-size: 1rem; margin: 0; line-height: 1.5; flex-grow: 1;">
                        <?php echo htmlspecialchars($spot['description']); ?>
                    </p>
                    <button class="btn btn-primary" style="margin-top: 1rem; width: fit-content; pointer-events: none;">
                        Book Experience
                        <i class="fa-solid fa-chevron-right" style="font-size: 0.8rem; margin-left: 0.5rem;"></i>
                    </button>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
    
    <?php if(empty($attractions)): ?>
        <div class="card text-center" style="padding: 4rem;">
            <p style="color: var(--text-muted); font-size: 1.2rem;">No spots currently available. Please check back later.</p>
        </div>
    <?php endif; ?>
</main>

<?php require 'includes/footer.php'; ?>
