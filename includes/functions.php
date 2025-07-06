<?php
// includes/functions.php
function get_db() {
    static $db = null;
    if ($db === null) {
        $db = new PDO('sqlite:' . __DIR__ . '/../db/database.sqlite');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    return $db;
}

function get_user($user_id) {
    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get display name for a user (prefer Telegram username, then first name, then database username)
function get_display_name($user) {
    if (is_array($user)) {
        if (!empty($user['telegram_username'])) {
            return '@' . $user['telegram_username'];
        } elseif (!empty($user['telegram_first_name'])) {
            return $user['telegram_first_name'];
        } else {
            return $user['username'];
        }
    }
    return 'Unknown User';
}

// Gift Card Functions
function generate_gift_card_code() {
    $prefix = "JAXXYCC-";
    $characters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $code = "";
    
    // Generate 12 random characters in groups of 4
    for ($i = 0; $i < 12; $i++) {
        if ($i > 0 && $i % 4 == 0) {
            $code .= "-";
        }
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $prefix . $code;
}

function create_gift_card($amount, $created_by) {
    $db = get_db();
    
    // Generate unique code
    do {
        $code = generate_gift_card_code();
        $stmt = $db->prepare('SELECT id FROM gift_cards WHERE code = ?');
        $stmt->execute([$code]);
        $exists = $stmt->fetch();
    } while ($exists);
    
    // Create gift card
    $stmt = $db->prepare('INSERT INTO gift_cards (code, amount, created_by) VALUES (?, ?, ?)');
    $result = $stmt->execute([$code, $amount, $created_by]);
    
    if ($result) {
        return [
            'success' => true,
            'code' => $code,
            'id' => $db->lastInsertId()
        ];
    } else {
        return ['success' => false, 'error' => 'Failed to create gift card'];
    }
}

function redeem_gift_card($code, $user_id) {
    $db = get_db();
    
    try {
        $db->beginTransaction();
        
        // Check if gift card exists and is active
        $stmt = $db->prepare('SELECT * FROM gift_cards WHERE code = ? AND is_active = 1 AND redeemed_by IS NULL');
        $stmt->execute([$code]);
        $gift_card = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$gift_card) {
            $db->rollback();
            return ['success' => false, 'error' => 'Gift card not found or already redeemed'];
        }
        
        // Check if user already redeemed this gift card
        $stmt = $db->prepare('SELECT id FROM gift_card_redemptions WHERE gift_card_id = ? AND user_id = ?');
        $stmt->execute([$gift_card['id'], $user_id]);
        if ($stmt->fetch()) {
            $db->rollback();
            return ['success' => false, 'error' => 'You have already redeemed this gift card'];
        }
        
        // Update user's wallet
        $stmt = $db->prepare('UPDATE wallets SET balance = balance + ? WHERE user_id = ?');
        $stmt->execute([$gift_card['amount'], $user_id]);
        
        // Mark gift card as redeemed
        $stmt = $db->prepare('UPDATE gift_cards SET redeemed_by = ?, redeemed_at = datetime("now") WHERE id = ?');
        $stmt->execute([$user_id, $gift_card['id']]);
        
        // Record redemption
        $stmt = $db->prepare('INSERT INTO gift_card_redemptions (gift_card_id, user_id, amount, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([
            $gift_card['id'],
            $user_id,
            $gift_card['amount'],
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        // Add transaction record
        $stmt = $db->prepare('INSERT INTO transactions (user_id, amount, type) VALUES (?, ?, ?)');
        $stmt->execute([$user_id, $gift_card['amount'], 'gift_card']);
        
        $db->commit();
        
        return [
            'success' => true,
            'amount' => $gift_card['amount'],
            'code' => $code
        ];
        
    } catch (Exception $e) {
        $db->rollback();
        return ['success' => false, 'error' => 'Error redeeming gift card: ' . $e->getMessage()];
    }
}

function get_gift_card_by_code($code) {
    $db = get_db();
    $stmt = $db->prepare('SELECT gc.*, u1.username as created_by_username, u2.username as redeemed_by_username FROM gift_cards gc LEFT JOIN users u1 ON gc.created_by = u1.id LEFT JOIN users u2 ON gc.redeemed_by = u2.id WHERE gc.code = ?');
    $stmt->execute([$code]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_user_gift_cards($user_id) {
    $db = get_db();
    $stmt = $db->prepare('SELECT gc.*, u.username as redeemed_by_username FROM gift_cards gc LEFT JOIN users u ON gc.redeemed_by = u.id WHERE gc.created_by = ? ORDER BY gc.created_at DESC');
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_all_gift_cards() {
    $db = get_db();
    $stmt = $db->prepare('SELECT gc.*, u1.username as created_by_username, u2.username as redeemed_by_username FROM gift_cards gc LEFT JOIN users u1 ON gc.created_by = u1.id LEFT JOIN users u2 ON gc.redeemed_by = u2.id ORDER BY gc.created_at DESC');
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
