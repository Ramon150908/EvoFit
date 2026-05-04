<?php
require_once __DIR__ . '/../bootstrap.php';
$uploadDir = __DIR__ . '/../uploads/avatars/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true); // true = cria diretórios recursivamente
}
header('Content-Type: application/json; charset=utf-8');

$user = requireAuth();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    if ($method === 'GET') {
        handleGetProfile($user);
    } elseif ($method === 'POST' && $action === 'update') {
        handleUpdateProfile($user);
    } elseif ($method === 'POST' && $action === 'avatar') {
        handleUploadAvatar($user);
    } elseif ($method === 'POST' && $action === 'password') {
        handleChangePassword($user);
    } elseif ($method === 'GET' && $action === 'stats') {
        handleGetStats($user);
    } elseif ($method === 'GET' && $action === 'goals') {
        // Buscar metas nutricionais
        handleGetGoals($user);
    } elseif ($method === 'POST' && $action === 'goals') {
        // Atualizar metas nutricionais
        handleUpdateGoals($user);
    } else {
        jsonError('Rota não encontrada.', 404);
    }
} catch (Exception $e) {
    jsonError('Erro interno: ' . $e->getMessage(), 500);
}

// ====================== BUSCAR PERFIL ======================
function handleGetProfile(array $user): never
{
    $profile = Database::fetchOne(
        'SELECT id, name, email, avatar_url, bio, phone, birth_date, gender,
                height_cm, weight_kg, fitness_goal, activity_level,
                daily_cal, daily_prot, daily_carb, daily_fat,
                created_at, updated_at
         FROM users 
         WHERE id = ?',
        [$user['id']]
    );
    
    if (!$profile) {
        jsonError('Usuário não encontrado.', 404);
    }
    
    // Formatar data de nascimento
    if ($profile['birth_date']) {
        $profile['birth_date_formatted'] = date('d/m/Y', strtotime($profile['birth_date']));
    }
    
    // Calcular IMC se tiver altura e peso
    if ($profile['height_cm'] && $profile['weight_kg']) {
        $heightM = $profile['height_cm'] / 100;
        $profile['bmi'] = round($profile['weight_kg'] / ($heightM * $heightM), 1);
        
        if ($profile['bmi'] < 18.5) $profile['bmi_category'] = 'Abaixo do peso';
        elseif ($profile['bmi'] < 25) $profile['bmi_category'] = 'Peso normal';
        elseif ($profile['bmi'] < 30) $profile['bmi_category'] = 'Sobrepeso';
        else $profile['bmi_category'] = 'Obesidade';
    }
    
    jsonResponse(['profile' => $profile]);
}

// ====================== ATUALIZAR PERFIL ======================
function handleUpdateProfile(array $user): never
{
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    
    $fields = [];
    $params = [];
    
    // Campos permitidos para atualização
    $allowedFields = [
        'name', 'bio', 'phone', 'birth_date', 'gender',
        'height_cm', 'weight_kg', 'fitness_goal', 'activity_level'
    ];
    
    foreach ($allowedFields as $field) {
        if (array_key_exists($field, $body)) {
            $fields[] = "$field = ?";
            
            // Validações específicas
            $value = $body[$field];
            
            if ($field === 'height_cm' && $value !== null) {
                $value = max(50, min(250, (float)$value));
            }
            if ($field === 'weight_kg' && $value !== null) {
                $value = max(20, min(300, (float)$value));
            }
            if ($field === 'birth_date' && $value) {
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    jsonError('Formato de data inválido. Use YYYY-MM-DD.');
                }
            }
            if ($field === 'name' && $value) {
                $value = trim($value);
                if (strlen($value) < 2) {
                    jsonError('Nome deve ter pelo menos 2 caracteres.');
                }
            }
            
            $params[] = $value === '' ? null : $value;
        }
    }
    
    if (empty($fields)) {
        jsonError('Nenhum campo para atualizar.');
    }
    
    $params[] = $user['id'];
    $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
    
    Database::query($sql, $params);
    
    // Atualizar sessão se o nome mudou
    if (isset($body['name'])) {
        $_SESSION['user_name'] = $body['name'];
    }
    
    // Buscar perfil atualizado
    $profile = Database::fetchOne('SELECT * FROM users WHERE id = ?', [$user['id']]);
    
    jsonResponse(['success' => true, 'profile' => $profile, 'message' => 'Perfil atualizado com sucesso!']);
}

// ====================== BUSCAR METAS ======================
function handleGetGoals(array $user): never
{
    $goals = Database::fetchOne(
        'SELECT daily_cal, daily_prot, daily_carb, daily_fat FROM users WHERE id = ?',
        [$user['id']]
    );
    jsonResponse(['goals' => $goals]);
}

// ====================== ATUALIZAR METAS ======================
function handleUpdateGoals(array $user): never
{
    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    $cal  = (int)($body['daily_cal']  ?? 0);
    $prot = (int)($body['daily_prot'] ?? 0);
    $carb = (int)($body['daily_carb'] ?? 0);
    $fat  = (int)($body['daily_fat']  ?? 0);

    if ($cal < 500 || $cal > 10000)   jsonError('Meta de calorias inválida (500–10000).');
    if ($prot < 10 || $prot > 500)    jsonError('Meta de proteína inválida (10–500g).');
    if ($carb < 10 || $carb > 1000)   jsonError('Meta de carboidratos inválida (10–1000g).');
    if ($fat  < 5  || $fat  > 500)    jsonError('Meta de gordura inválida (5–500g).');

    Database::query(
        'UPDATE users SET daily_cal = ?, daily_prot = ?, daily_carb = ?, daily_fat = ? WHERE id = ?',
        [$cal, $prot, $carb, $fat, $user['id']]
    );

    jsonResponse(['success' => true, 'message' => 'Metas atualizadas com sucesso!']);
}

// ====================== UPLOAD DE AVATAR ======================
function handleUploadAvatar(array $user): never
{
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        jsonError('Erro no upload do arquivo.');
    }
    
    $file = $_FILES['avatar'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    
    if (!in_array($file['type'], $allowedTypes)) {
        jsonError('Tipo de arquivo inválido. Use JPG, PNG, WEBP ou GIF.');
    }
    
    if ($file['size'] > 5 * 1024 * 1024) {
        jsonError('Arquivo muito grande. Máximo 5MB.');
    }
    
    // Criar diretório se não existir
    $uploadDir = __DIR__ . '/../uploads/avatars/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Gerar nome único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $user['id'] . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        jsonError('Erro ao salvar o arquivo.');
    }
    
    // URL pública do avatar
    $avatarUrl = APP_URL . '/uploads/avatars/' . $filename;
    
    // Atualizar no banco
    Database::query('UPDATE users SET avatar_url = ? WHERE id = ?', [$avatarUrl, $user['id']]);
    
    jsonResponse(['success' => true, 'avatar_url' => $avatarUrl, 'message' => 'Avatar atualizado!']);
}

// ====================== ALTERAR SENHA ======================
function handleChangePassword(array $user): never
{
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    
    $currentPassword = $body['current_password'] ?? '';
    $newPassword = $body['new_password'] ?? '';
    $confirmPassword = $body['confirm_password'] ?? '';
    
    if (strlen($newPassword) < 6) {
        jsonError('Nova senha deve ter pelo menos 6 caracteres.');
    }
    
    if ($newPassword !== $confirmPassword) {
        jsonError('As senhas não coincidem.');
    }
    
    // Buscar usuário
    $dbUser = Database::fetchOne(
        'SELECT password_hash FROM users WHERE id = ? AND provider = "email"',
        [$user['id']]
    );
    
    if (!$dbUser || !$dbUser['password_hash']) {
        jsonError('Usuários do Google não podem alterar senha pelo site. Use o Google para login.');
    }
    
    if (!password_verify($currentPassword, $dbUser['password_hash'])) {
        jsonError('Senha atual incorreta.');
    }
    
    $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
    Database::query('UPDATE users SET password_hash = ? WHERE id = ?', [$newHash, $user['id']]);
    
    jsonResponse(['success' => true, 'message' => 'Senha alterada com sucesso!']);
}

// ====================== ESTATÍSTICAS DO USUÁRIO ======================
function handleGetStats(array $user): never
{
    // Total de alimentos registrados
    $foodStats = Database::fetchOne(
        'SELECT COUNT(*) as total_foods, SUM(cal) as total_calories 
         FROM food_logs 
         WHERE user_id = ?',
        [$user['id']]
    );
    
    // Total de treinos
    $workoutStats = Database::fetchOne(
        'SELECT COUNT(*) as total_workouts, SUM(duration_min) as total_minutes 
         FROM workout_logs 
         WHERE user_id = ?',
        [$user['id']]
    );
    
    // Dias ativos (dias com pelo menos um registro)
    $activeDays = Database::fetchOne(
        'SELECT COUNT(DISTINCT logged_at) as active_days 
         FROM food_logs 
         WHERE user_id = ?',
        [$user['id']]
    );
    
    // Média diária de calorias (últimos 30 dias)
    $avgCalories = Database::fetchOne(
        'SELECT ROUND(AVG(daily_cal)) as avg_calories 
         FROM (
             SELECT logged_at, SUM(cal) as daily_cal 
             FROM food_logs 
             WHERE user_id = ? AND logged_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             GROUP BY logged_at
         ) as daily_data',
        [$user['id']]
    );
    
    jsonResponse([
        'stats' => [
            'total_foods' => (int)($foodStats['total_foods'] ?? 0),
            'total_calories' => (int)($foodStats['total_calories'] ?? 0),
            'total_workouts' => (int)($workoutStats['total_workouts'] ?? 0),
            'total_minutes' => (int)($workoutStats['total_minutes'] ?? 0),
            'active_days' => (int)($activeDays['active_days'] ?? 0),
            'avg_daily_calories' => (int)($avgCalories['avg_calories'] ?? 0),
        ]
    ]);
}
?>