<?php
// thank_you.php - Purchase success page
session_start();
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

// Check if we have purchase success data
if (!isset($_SESSION['purchase_success'])) {
    header('Location: /store.php');
    exit;
}

$purchaseData = $_SESSION['purchase_success'];
unset($_SESSION['purchase_success']); // Clear the session data

$item = $purchaseData['item'];
$userBalance = $purchaseData['balance'];

include __DIR__ . '/includes/header.php';
?>

<div class="card" style="max-width: 600px; text-align: center;">
    <!-- Success Animation -->
    <div class="success-animation">
        <div class="checkmark-circle">
            <div class="checkmark">
                <div class="checkmark-stem"></div>
                <div class="checkmark-kick"></div>
            </div>
        </div>
    </div>
    
    <h2 style="color: #10b981; margin: 2rem 0 1rem 0;">🎉 Purchase Successful!</h2>
    
    <p style="font-size: 1.2rem; color: #e8e8e8; margin-bottom: 2rem;">
        Thank you for your purchase! Your item has been unlocked.
    </p>
    
    <!-- Purchase Details -->
    <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 12px; padding: 2rem; margin: 2rem 0; text-align: left;">
        <h3 style="margin: 0 0 1rem 0; color: #10b981; text-align: center;">📦 Purchase Details</h3>
        
        <div style="display: grid; gap: 1rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid rgba(16, 185, 129, 0.2);">
                <strong>Item:</strong>
                <span><?= htmlspecialchars($item['title']) ?></span>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid rgba(16, 185, 129, 0.2);">
                <strong>Price:</strong>
                <span style="color: #10b981; font-weight: bold;">$<?= number_format($item['price'], 2) ?></span>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid rgba(16, 185, 129, 0.2);">
                <strong>Purchase Date:</strong>
                <span><?= date('F j, Y \a\t g:i A') ?></span>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0;">
                <strong>Remaining Balance:</strong>
                <span style="color: #60a5fa; font-weight: bold;">$<?= number_format($userBalance, 2) ?></span>
            </div>
        </div>
    </div>
    
    <!-- What's Next -->
    <div style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 12px; padding: 1.5rem; margin: 2rem 0;">
        <h4 style="margin: 0 0 1rem 0; color: #60a5fa;">🚀 What's Next?</h4>
        <p style="color: #a8a8a8; margin: 0; text-align: left;">
            ✅ Your purchase has been processed successfully<br>
            ✅ A confirmation has been sent to your Telegram<br>
            ✅ You can now access your purchased content<br>
            ✅ The item has been added to your account
        </p>
    </div>
    
    <!-- Action Buttons -->
    <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem; flex-wrap: wrap;">
        <form method="post" action="/purchase.php" style="margin: 0;">
            <input type="hidden" name="reveal" value="<?= $item['id'] ?>">
            <button type="submit" style="width: auto; padding: 1rem 2rem; background: #10b981; color: white; border: none; font-size: 1rem; border-radius: 8px; cursor: pointer;">
                🔓 View Content
            </button>
        </form>
        
        <a href="/store.php" style="text-decoration: none;">
            <button style="width: auto; padding: 1rem 2rem; background: rgba(255,255,255,0.1); color: #e8e8e8; border: none; font-size: 1rem; border-radius: 8px; cursor: pointer;">
                🛍️ Continue Shopping
            </button>
        </a>
        
        <a href="/dashboard.php" style="text-decoration: none;">
            <button style="width: auto; padding: 1rem 2rem; background: rgba(99, 102, 241, 0.2); color: #a8a8f0; border: none; font-size: 1rem; border-radius: 8px; cursor: pointer;">
                💰 View Dashboard
            </button>
        </a>
    </div>
</div>

<script>
// Ensure animation triggers properly
document.addEventListener('DOMContentLoaded', function() {
    // Force a reflow to ensure animations start
    const animation = document.querySelector('.success-animation');
    if (animation) {
        animation.offsetHeight; // Force reflow
        
        // Add a class to trigger animation if needed
        animation.classList.add('loaded');
    }
    
    // Check if animations are supported and working
    const checkmarkCircle = document.querySelector('.checkmark-circle');
    if (checkmarkCircle) {
        // Add event listener for animation completion
        checkmarkCircle.addEventListener('animationend', function(e) {
            if (e.animationName === 'scaleIn') {
                console.log('Checkmark circle animation completed');
            }
        });
    }
    
    // Fallback: If animation doesn't seem to work after 3 seconds, ensure visibility
    setTimeout(function() {
        const stems = document.querySelectorAll('.checkmark-stem, .checkmark-kick');
        stems.forEach(function(stem) {
            if (stem.style.opacity !== '1') {
                stem.style.opacity = '1';
                stem.style.height = stem.classList.contains('checkmark-stem') ? '20px' : '4px';
                stem.style.width = stem.classList.contains('checkmark-kick') ? '12px' : '4px';
            }
        });
    }, 3000);
});
</script>

<style>
/* Success Animation Styles */
.success-animation {
    margin: 2rem 0;
    display: flex;
    justify-content: center;
    align-items: center;
}

.checkmark-circle {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: #10b981;
    position: relative;
    animation: scaleIn 0.8s ease-out;
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.2);
    display: flex;
    justify-content: center;
    align-items: center;
}

.checkmark {
    position: relative;
    width: 40px;
    height: 40px;
}

.checkmark-stem {
    position: absolute;
    width: 4px;
    height: 20px;
    background: white;
    left: 18px;
    top: 8px;
    transform: rotate(45deg);
    transform-origin: bottom;
    animation: checkmarkStem 0.6s ease-out 0.8s both;
    border-radius: 2px;
}

.checkmark-kick {
    position: absolute;
    width: 12px;
    height: 4px;
    background: white;
    left: 10px;
    top: 22px;
    transform: rotate(-45deg);
    transform-origin: left;
    animation: checkmarkKick 0.6s ease-out 1.2s both;
    border-radius: 2px;
}

@keyframes scaleIn {
    0% {
        transform: scale(0);
        opacity: 0;
    }
    50% {
        transform: scale(1.1);
        opacity: 1;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

@keyframes checkmarkStem {
    0% {
        height: 0;
        opacity: 0;
    }
    50% {
        opacity: 1;
    }
    100% {
        height: 20px;
        opacity: 1;
    }
}

@keyframes checkmarkKick {
    0% {
        width: 0;
        opacity: 0;
    }
    50% {
        opacity: 1;
    }
    100% {
        width: 12px;
        opacity: 1;
    }
}

/* Add a pulse effect for extra emphasis */
.checkmark-circle::before {
    content: '';
    position: absolute;
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: rgba(16, 185, 129, 0.1);
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    animation: pulse 2s ease-out 1.8s infinite;
}

@keyframes pulse {
    0% {
        transform: translate(-50%, -50%) scale(0.8);
        opacity: 0.8;
    }
    100% {
        transform: translate(-50%, -50%) scale(1.2);
        opacity: 0;
    }
}

/* Add a loading state to ensure animation triggers */
.success-animation.loaded .checkmark-circle {
    animation: scaleIn 0.8s ease-out;
}

.success-animation.loaded .checkmark-stem {
    animation: checkmarkStem 0.6s ease-out 0.8s both;
}

.success-animation.loaded .checkmark-kick {
    animation: checkmarkKick 0.6s ease-out 1.2s both;
}

/* Hover effects for buttons */
button:hover {
    transform: translateY(-2px);
    transition: all 0.3s ease;
}

/* Responsive design */
@media (max-width: 600px) {
    .card {
        margin: 1rem;
        padding: 1rem;
    }
    
    .checkmark-circle {
        width: 80px;
        height: 80px;
    }
    
    .checkmark {
        width: 32px;
        height: 32px;
    }
    
    .checkmark-stem {
        height: 16px;
        left: 14px;
        top: 6px;
    }
    
    .checkmark-kick {
        width: 10px;
        left: 8px;
        top: 18px;
    }
    
    div[style*="display: flex"] {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    button {
        width: 100% !important;
        margin: 0.25rem 0 !important;
    }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
