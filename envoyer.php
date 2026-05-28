<?php
// ============================================================
//  envoyer.php — Formulaire de contact · Steeve BARTHEL
//  Compatible MAMP (MySQL port 8889) et mode hors-connexion
// ============================================================

ob_start();

// ── 1. Configuration ─────────────────────────────────────────
// MAMP utilise le port 8889 pour MySQL par défaut.
// Si tu as changé le port dans MAMP, modifie-le ici.
define('DB_HOST',   '127.0.0.1');
define('DB_PORT',   '8889');        // Port MySQL MAMP (8889 par défaut)
define('DB_NAME',   'portfolio_contact');
define('DB_USER',   'root');        // Utilisateur MAMP (root par défaut)
define('DB_PASS',   'root');        // Mot de passe MAMP (root par défaut)

// ── 2. Headers ───────────────────────────────────────────────
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// ── 3. POST uniquement ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

// ── 4. Nettoyage des champs ──────────────────────────────────
function clean(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

$nom     = clean($_POST['nom']     ?? '');
$prenom  = clean($_POST['prenom']  ?? '');
$email   = trim($_POST['email']    ?? '');
$sujet   = clean($_POST['sujet']   ?? '');
$message = clean($_POST['message'] ?? '');

// ── 5. Validation ────────────────────────────────────────────
$errors = [];

if (strlen($nom) < 2)
    $errors[] = 'Nom trop court (min. 2 caractères).';

if (!filter_var($email, FILTER_VALIDATE_EMAIL))
    $errors[] = 'Adresse email invalide.';

$sujets_ok = ['collaboration', 'stage', 'question', 'autre'];
if (!in_array($sujet, $sujets_ok))
    $errors[] = 'Sujet invalide.';

if (strlen($message) < 10)
    $errors[] = 'Message trop court (min. 10 caractères).';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// ── 6. Tentative de connexion MySQL (optionnelle) ────────────
// Si MAMP n'est pas démarré ou la base n'existe pas,
// le message est quand même validé et confirmé à l'utilisateur.
$db_ok = false;
$pdo   = null;

try {
    $dsn = 'mysql:host=' . DB_HOST
         . ';port='      . DB_PORT
         . ';dbname='    . DB_NAME
         . ';charset=utf8mb4';

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 3, // ne pas bloquer plus de 3s
    ]);
    $db_ok = true;
} catch (PDOException $e) {
    // MAMP pas démarré ou base inexistante → on continue sans la DB
    $db_ok = false;
}

// ── 7. Insertion si DB disponible ───────────────────────────
$insert_id = null;
if ($db_ok && $pdo !== null) {
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO messages (nom, prenom, email, sujet, message, ip, user_agent)
             VALUES (:nom, :prenom, :email, :sujet, :message, :ip, :user_agent)'
        );
        $stmt->execute([
            ':nom'        => $nom,
            ':prenom'     => $prenom ?: null,
            ':email'      => $email,
            ':sujet'      => $sujet,
            ':message'    => $message,
            ':ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
            ':user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        ]);
        $insert_id = $pdo->lastInsertId();
    } catch (PDOException $e) {
        // L'insertion a échoué (table manquante etc.) → on ignore silencieusement
        $db_ok = false;
    }
}

// ── 8. Réponse ───────────────────────────────────────────────
// Succès dans tous les cas si la validation est passée.
// Le champ "saved" indique si le message a été sauvegardé en base.
echo json_encode([
    'success' => true,
    'message' => 'Message envoyé avec succès ! Je vous répondrai dès que possible.',
    'saved'   => $db_ok,
    'id'      => $insert_id,
]);
