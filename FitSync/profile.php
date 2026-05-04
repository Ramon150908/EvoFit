<?php
require_once __DIR__ . '/bootstrap.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userName = htmlspecialchars($_SESSION['user_name'] ?? 'Usuário');
$userFirst = explode(' ', trim($userName))[0];
$initials = implode('', array_map(fn($p) => strtoupper($p[0] ?? ''), array_slice(explode(' ', $userName), 0, 2)));

$dbUser = Database::fetchOne('SELECT avatar_url FROM users WHERE id = ?', [$_SESSION['user_id']]);
$avatar = $dbUser['avatar_url'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitSync — Meu Perfil</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        .profile-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .profile-header {
            background: white;
            border-radius: 1.5rem;
            border: 1px solid var(--border);
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .profile-avatar {
            position: relative;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .profile-avatar img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
        }
        
        .avatar-overlay {
            position: absolute;
            bottom: 0;
            right: 0;
            background: var(--primary);
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: white;
            border: 2px solid white;
        }
        
        .profile-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .profile-email {
            color: var(--gray-500);
            margin-bottom: 1rem;
        }
        
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .stat-card {
            background: var(--gray-50);
            border-radius: 1rem;
            padding: 1rem;
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .stat-label {
            font-size: 0.75rem;
            color: var(--gray-500);
            text-transform: uppercase;
        }
        
        .profile-section {
            background: white;
            border-radius: 1.5rem;
            border: 1px solid var(--border);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .section-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .form-group label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--gray-500);
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .btn-save {
            background: var(--primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-save:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: var(--gray-100);
            color: var(--gray-700);
            padding: 0.75rem 1.5rem;
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-secondary:hover {
            background: var(--gray-200);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
            display: none;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .hidden-upload {
            display: none;
        }
        
        .bmi-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        
        @media (max-width: 640px) {
            .profile-container {
                padding: 1rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<header class="main-header">
    <div class="navbar">
        <div class="logo">
            <?php if (file_exists(__DIR__ . '/assets/icon.png')): ?>
                <img src="assets/icon.png" alt="FitSync" class="logo-img">
            <?php else: ?>
                <div class="logo-text">Fit<span>Sync</span></div>
            <?php endif; ?>
        </div>
        
        <nav class="nav-tabs" aria-label="Navegação principal">
            <button class="nav-tab" data-panel="panelDiary" onclick="window.location.href='index.php'">
                <span class="tab-icon" aria-hidden="true">📋</span>
                <span class="tab-label">Diário</span>
            </button>
            <button class="nav-tab" data-panel="panelWorkout" onclick="window.location.href='index.php'">
                <span class="tab-icon" aria-hidden="true">💪</span>
                <span class="tab-label">Treinos</span>
            </button>
            <button class="nav-tab active" data-panel="panelProfile">
                <span class="tab-icon" aria-hidden="true">👤</span>
                <span class="tab-label">Perfil</span>
            </button>
        </nav>
        
        <div class="navbar-right">
            <div class="user-badge">
                <?php if ($avatar): ?>
                    <img class="user-avatar" src="<?= htmlspecialchars($avatar) ?>" alt="Avatar">
                <?php else: ?>
                    <div class="user-initials"><?= $initials ?></div>
                <?php endif; ?>
                <span class="user-name"><?= $userFirst ?></span>
                <button class="btn-logout" id="btnLogout" title="Sair">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</header>

<div class="profile-container">
    <div id="alert" class="alert"></div>
    
    <!-- Header do Perfil -->
    <div class="profile-header">
        <div class="profile-avatar">
            <img id="avatarImg" src="<?= htmlspecialchars($avatar ?? 'assets/default-avatar.png') ?>" alt="Avatar">
            <div class="avatar-overlay" onclick="document.getElementById('avatarInput').click()">
                📷
            </div>
        </div>
        <form id="avatarForm" enctype="multipart/form-data" style="display:none;">
            <input type="file" id="avatarInput" name="avatar" accept="image/*" class="hidden-upload">
        </form>
        <div class="profile-name" id="profileName"><?= htmlspecialchars($userName) ?></div>
        <div class="profile-email" id="profileEmail"></div>
        
        <!-- Estatísticas Rápidas -->
        <div class="profile-stats" id="statsContainer">
            <div class="stat-card">
                <div class="stat-value" id="statFoods">-</div>
                <div class="stat-label">Alimentos</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="statWorkouts">-</div>
                <div class="stat-label">Treinos</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="statDays">-</div>
                <div class="stat-label">Dias Ativos</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="statCalories">-</div>
                <div class="stat-label">Média kcal/dia</div>
            </div>
        </div>
    </div>
    
    <!-- Informações Pessoais -->
    <div class="profile-section">
        <div class="section-title">
            Informações Pessoais
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label>Nome completo</label>
                <input type="text" id="name" placeholder="Seu nome">
            </div>
            <div class="form-group">
                <label>Telefone</label>
                <input type="tel" id="phone" placeholder="(00) 00000-0000">
            </div>
            <div class="form-group">
                <label>Data de nascimento</label>
                <input type="date" id="birth_date">
            </div>
            <div class="form-group">
                <label>Gênero</label>
                <select id="gender">
                    <option value="">Selecione</option>
                    <option value="male">Masculino</option>
                    <option value="female">Feminino</option>
                    <option value="other">Outro</option>
                    <option value="prefer_not_to_say">Prefiro não dizer</option>
                </select>
            </div>
        </div>
        <div class="form-group" style="margin-top: 1rem;">
            <label>Biografia</label>
            <textarea id="bio" rows="3" placeholder="Conte um pouco sobre você, seus objetivos e motivações..."></textarea>
        </div>
        <div style="margin-top: 1rem; text-align: right;">
            <button class="btn-save" onclick="saveProfile()">Salvar Alterações</button>
        </div>
    </div>
    
    <!-- Dados Físicos e Objetivos -->
    <div class="profile-section">
        <div class="section-title">
            Dados Físicos e Objetivos
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label>Altura (cm)</label>
                <input type="number" id="height_cm" step="0.01" placeholder="Ex: 175">
            </div>
            <div class="form-group">
                <label>Peso (kg)</label>
                <input type="number" id="weight_kg" step="0.1" placeholder="Ex: 70.5">
            </div>
            <div class="form-group">
                <label>Nível de atividade</label>
                <select id="activity_level">
                    <option value="">Selecione</option>
                    <option value="sedentary">Sedentário (pouco ou nenhum exercício)</option>
                    <option value="light">Leve (exercício 1-3x/semana)</option>
                    <option value="moderate">Moderado (exercício 3-5x/semana)</option>
                    <option value="very_active">Muito ativo (exercício 6-7x/semana)</option>
                    <option value="extra_active">Extremamente ativo (atleta)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Objetivo fitness</label>
                <select id="fitness_goal">
                    <option value="">Selecione</option>
                    <option value="weight_loss">Perda de peso</option>
                    <option value="muscle_gain">Ganho de massa muscular</option>
                    <option value="maintenance">Manutenção</option>
                    <option value="endurance">Resistência / Condicionamento</option>
                    <option value="flexibility">Flexibilidade / Mobilidade</option>
                </select>
            </div>
        </div>
        
        <div id="bmiContainer" style="margin-top: 1rem; display: none;">
            <div class="alert alert-success" style="display: block;">
                <strong>📊 Seu IMC:</strong> <span id="bmiValue"></span> - <span id="bmiCategory"></span>
            </div>
        </div>
        
        <div style="margin-top: 1rem; text-align: right;">
            <button class="btn-save" onclick="saveProfile()">Salvar Alterações</button>
        </div>
    </div>
    
    <!-- Metas Nutricionais -->
<div class="profile-section">
    <div class="section-title">
        Metas Nutricionais
        <button class="btn-secondary" style="font-size: 0.75rem; padding: 0.5rem 1rem;" onclick="saveGoals()">
            ✏️ Ajustar Metas
        </button>
    </div>
    <div class="form-grid" id="goalsContainer">
        <div class="stat-card">
            <div class="stat-value" id="goalCalories">-</div>
            <div class="stat-label">Calorias/dia</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" id="goalProtein">-</div>
            <div class="stat-label">Proteína/dia (g)</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" id="goalCarbs">-</div>
            <div class="stat-label">Carboidratos/dia (g)</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" id="goalFat">-</div>
            <div class="stat-label">Gordura/dia (g)</div>
        </div>
    </div>
</div>
    
    <!-- Segurança (Alterar Senha) -->
    <div class="profile-section" id="passwordSection">
        <div class="section-title">
            Segurança
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label>Senha atual</label>
                <input type="password" id="current_password" placeholder="Digite sua senha atual">
            </div>
            <div class="form-group">
                <label>Nova senha</label>
                <input type="password" id="new_password" placeholder="Mínimo 6 caracteres">
            </div>
            <div class="form-group">
                <label>Confirmar nova senha</label>
                <input type="password" id="confirm_password" placeholder="Confirme a nova senha">
            </div>
        </div>
        <div style="margin-top: 1rem; text-align: right;">
            <button class="btn-save" onclick="changePassword()">Alterar Senha</button>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
// Função auxiliar para mostrar mensagens
function showMessage(message, isError = false) {
    const alert = document.getElementById('alert');
    alert.textContent = message;
    alert.className = `alert ${isError ? 'alert-error' : 'alert-success'}`;
    alert.style.display = 'block';
    setTimeout(() => {
        alert.style.display = 'none';
    }, 5000);
}

// Função para chamar a API
async function api(url, options = {}) {
    const response = await fetch(url, {
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        ...options
    });
    return response.json();
}

// Carregar perfil e metas juntos
async function loadProfile() {
    try {
        // Carregar perfil
        const profileData = await api('api/profile.php');
        if (profileData.profile) {
            const p = profileData.profile;
            
            document.getElementById('profileName').textContent = p.name;
            document.getElementById('profileEmail').textContent = p.email;
            
            document.getElementById('name').value = p.name || '';
            document.getElementById('phone').value = p.phone || '';
            document.getElementById('birth_date').value = p.birth_date || '';
            document.getElementById('gender').value = p.gender || '';
            document.getElementById('bio').value = p.bio || '';
            document.getElementById('height_cm').value = p.height_cm || '';
            document.getElementById('weight_kg').value = p.weight_kg || '';
            document.getElementById('activity_level').value = p.activity_level || '';
            document.getElementById('fitness_goal').value = p.fitness_goal || '';
            
            // IMC
            if (p.bmi) {
                document.getElementById('bmiContainer').style.display = 'block';
                document.getElementById('bmiValue').textContent = p.bmi;
                document.getElementById('bmiCategory').textContent = p.bmi_category;
            }
            
            // Metas - usar os valores do perfil
            document.getElementById('goalCalories').textContent = p.daily_cal || 2000;
            document.getElementById('goalProtein').textContent = p.daily_prot || 150;
            document.getElementById('goalCarbs').textContent = p.daily_carb || 250;
            document.getElementById('goalFat').textContent = p.daily_fat || 65;
            
            // Avatar
            if (p.avatar_url) {
                document.getElementById('avatarImg').src = p.avatar_url;
            }
        }
    } catch (error) {
        console.error('Erro ao carregar perfil:', error);
        showMessage('Erro ao carregar perfil', true);
    }
}

// Salvar perfil
async function saveProfile() {
    const payload = {
        name: document.getElementById('name').value,
        phone: document.getElementById('phone').value,
        birth_date: document.getElementById('birth_date').value,
        gender: document.getElementById('gender').value,
        bio: document.getElementById('bio').value,
        height_cm: parseFloat(document.getElementById('height_cm').value) || null,
        weight_kg: parseFloat(document.getElementById('weight_kg').value) || null,
        activity_level: document.getElementById('activity_level').value,
        fitness_goal: document.getElementById('fitness_goal').value
    };
    
    try {
        const data = await api('api/profile.php?action=update', {
            method: 'POST',
            body: JSON.stringify(payload)
        });
        
        if (data.success) {
            showMessage(data.message);
            loadProfile(); // Recarregar para atualizar IMC e outras informações
        } else {
            showMessage(data.error || 'Erro ao salvar', true);
        }
    } catch (error) {
        showMessage('Erro de conexão', true);
    }
}

// Upload de avatar
document.getElementById('avatarInput').addEventListener('change', async function(e) {
    if (!this.files.length) return;
    
    const formData = new FormData();
    formData.append('avatar', this.files[0]);
    
    try {
        const response = await fetch('api/profile.php?action=avatar', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('avatarImg').src = data.avatar_url + '?t=' + Date.now();
            showMessage('Avatar atualizado com sucesso!');
        } else {
            showMessage(data.error || 'Erro ao enviar avatar', true);
        }
    } catch (error) {
        showMessage('Erro ao enviar avatar', true);
    }
});

// Carregar estatísticas
async function loadStats() {
    try {
        const data = await api('api/profile.php?action=stats');
        if (data.stats) {
            document.getElementById('statFoods').textContent = data.stats.total_foods;
            document.getElementById('statWorkouts').textContent = data.stats.total_workouts;
            document.getElementById('statDays').textContent = data.stats.active_days;
            document.getElementById('statCalories').textContent = data.stats.avg_daily_calories;
        }
    } catch (error) {
        console.error('Erro ao carregar estatísticas:', error);
    }
}

// Alterar senha
async function changePassword() {
    const current = document.getElementById('current_password').value;
    const newPass = document.getElementById('new_password').value;
    const confirm = document.getElementById('confirm_password').value;
    
    if (!current || !newPass || !confirm) {
        showMessage('Preencha todos os campos de senha', true);
        return;
    }
    
    if (newPass !== confirm) {
        showMessage('As novas senhas não coincidem', true);
        return;
    }
    
    if (newPass.length < 6) {
        showMessage('A nova senha deve ter pelo menos 6 caracteres', true);
        return;
    }
    
    try {
        const data = await api('api/profile.php?action=password', {
            method: 'POST',
            body: JSON.stringify({
                current_password: current,
                new_password: newPass,
                confirm_password: confirm
            })
        });
        
        if (data.success) {
            showMessage(data.message);
            document.getElementById('current_password').value = '';
            document.getElementById('new_password').value = '';
            document.getElementById('confirm_password').value = '';
        } else {
            showMessage(data.error || 'Erro ao alterar senha', true);
        }
    } catch (error) {
        showMessage('Erro de conexão', true);
    }
}

// Logout
document.getElementById('btnLogout')?.addEventListener('click', async () => {
    if (confirm('Deseja sair da sua conta?')) {
        await fetch('api/auth.php?action=logout', { method: 'GET' });
        window.location.href = 'login.php';
    }
});

// Inicializar
document.addEventListener('DOMContentLoaded', () => {
    loadProfile();
    loadStats();
});

// Salvar metas nutricionais
async function saveGoals() {
    const calories = prompt('Digite sua meta diária de calorias (500-10000):', 
                            document.getElementById('goalCalories').textContent);
    if (!calories) return;
    
    const protein = prompt('Digite sua meta diária de proteína em gramas (10-500):', 
                           document.getElementById('goalProtein').textContent);
    if (!protein) return;
    
    const carbs = prompt('Digite sua meta diária de carboidratos em gramas (10-1000):', 
                         document.getElementById('goalCarbs').textContent);
    if (!carbs) return;
    
    const fat = prompt('Digite sua meta diária de gordura em gramas (5-500):', 
                       document.getElementById('goalFat').textContent);
    if (!fat) return;
    
    const payload = {
        daily_cal: parseInt(calories),
        daily_prot: parseInt(protein),
        daily_carb: parseInt(carbs),
        daily_fat: parseInt(fat)
    };
    
    try {
        // Salvar no perfil
        const data = await api('api/profile.php?action=goals', {
            method: 'POST',
            body: JSON.stringify(payload)
        });
        
        if (data.success) {
            showMessage(data.message);
            
            // Atualizar exibição no perfil
            document.getElementById('goalCalories').textContent = payload.daily_cal;
            document.getElementById('goalProtein').textContent = payload.daily_prot;
            document.getElementById('goalCarbs').textContent = payload.daily_carb;
            document.getElementById('goalFat').textContent = payload.daily_fat;
            
            // Também salvar via foods.php para sincronizar com o diário
            await fetch('api/foods.php?action=goals', {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
                credentials: 'same-origin'
            });
            
            // Se estiver na página do diário, recarregar os dados
            if (typeof loadLogs === 'function') {
                await loadLogs();
            }
        } else {
            showMessage(data.error || 'Erro ao salvar metas', true);
        }
    } catch (error) {
        showMessage('Erro de conexão', true);
    }
}
</script>
</body>
</html>
