<?php
/*
Plugin Name: 5D Therapists Plugin
Description: Plugin personalizado para gerenciar o app 5D Therapists no WordPress, incluindo login, cadastro, dashboard dinâmico e agendamento de sessões.
Version: 1.3
Author: Mestre Ricardo
*/

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Registro de ativação para criar tabelas no banco de dados
function therapists_plugin_activation() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Tabela para armazenar sessões
    $sessions_table = $wpdb->prefix . 'therapists_sessions';
    $sql = "CREATE TABLE $sessions_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        therapist_id bigint(20) NOT NULL,
        patient_name varchar(100) NOT NULL,
        session_date datetime NOT NULL,
        notes text,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'therapists_plugin_activation');

// Shortcode para a página de login
function therapists_login_form_shortcode() {
    ob_start();
    ?>
    <div id="login-container" class="login-container">
        <div class="login-box">
            <h2 class="quantum-text">Login - 5D Therapists</h2>
            <div class="energy-bar"></div>
            <input type="text" id="username" placeholder="Usuário" class="quantum-input">
            <input type="password" id="password" placeholder="Senha" class="quantum-input">
            <button onclick="therapistsLogin()" class="quantum-button">Entrar</button>
        </div>
    </div>
    <script>
        function therapistsLogin() {
            var username = document.getElementById("username").value;
            var password = document.getElementById("password").value;
            if (username === "therapist" && password === "5dpassword") {
                window.location.href = '/wp-admin/admin.php?page=therapists_dashboard';
            } else {
                alert("Credenciais inválidas. Por favor, tente novamente.");
            }
        }
    </script>
    <style>
        #login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(135deg, #00c9fe, #4facfe);
        }
        .login-box {
            background: rgba(255, 255, 255, 0.95);
            padding: 2.5em;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            text-align: center;
            width: 320px;
            position: relative;
            overflow: hidden;
        }
        .login-box h2 {
            margin-bottom: 1.5em;
            color: #333;
        }
        .quantum-input {
            width: 100%;
            padding: 0.8em;
            margin-bottom: 1em;
            border: 2px solid rgba(79, 172, 254, 0.2);
            border-radius: 8px;
            box-sizing: border-box;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }
        .quantum-input:focus {
            border-color: #4facfe;
            box-shadow: 0 0 10px rgba(79, 172, 254, 0.2);
            outline: none;
        }
        .quantum-button {
            background: linear-gradient(135deg, #00c9fe, #4facfe);
            color: white;
            padding: 0.8em;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .quantum-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 201, 254, 0.3);
        }
        .quantum-text {
            background: linear-gradient(135deg, #00c9fe, #4facfe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: bold;
        }
        .energy-bar {
            height: 4px;
            background: linear-gradient(90deg, #00c9fe, #4facfe);
            width: 100px;
            margin: -1em auto 1.5em;
            border-radius: 2px;
            position: relative;
            overflow: hidden;
        }
        .energy-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            animation: energyFlow 2s linear infinite;
        }
        @keyframes energyFlow {
            0% { left: -100%; }
            100% { left: 100%; }
        }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('therapists_login', 'therapists_login_form_shortcode');

// Página de configuração no painel do WordPress
function therapists_dashboard_menu() {
    add_menu_page(
        '5D Therapists Dashboard',
        '5D Therapists',
        'manage_options',
        'therapists_dashboard',
        'therapists_dashboard_page',
        'dashicons-admin-site',
        20
    );
}
add_action('admin_menu', 'therapists_dashboard_menu');

// Conteúdo do Dashboard principal
function therapists_dashboard_page() {
    ?>
    <div class="wrap">
        <div class="dashboard-header">
            <h1><span class="quantum-text">5D</span> Therapists Dashboard</h1>
            <div class="energy-bar"></div>
        </div>
        
        <div id="dashboard-container" class="dashboard-quantum-container">
            <!-- Cards principais -->
            <div class="quantum-card patients-card">
                <div class="card-quantum-icon">
                    <svg viewBox="0 0 24 24" class="quantum-svg">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-14c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4z" fill="currentColor"/>
                    </svg>
                </div>
                <div class="card-quantum-content">
                    <div class="card-title">Pacientes</div>
                    <p>Visualize e gerencie seus pacientes.</p>
                    <div class="quantum-stats">
                        <span class="stat">Total: 42</span>
                        <span class="stat">Ativos: 28</span>
                    </div>
                    <button class="quantum-button" onclick="viewPatients()">Ver Pacientes</button>
                </div>
                <div class="quantum-effect"></div>
            </div>

            <div class="quantum-card reports-card">
                <div class="card-quantum-icon">
                    <svg viewBox="0 0 24 24" class="quantum-svg">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z" fill="currentColor"/>
                        <path d="M7 12h2v5H7zm4-3h2v8h-2zm4-3h2v11h-2z" fill="currentColor"/>
                    </svg>
                </div>
                <div class="card-quantum-content">
                    <div class="card-title">Relatórios</div>
                    <p>Acesse relatórios de progresso e análises.</p>
                    <div class="quantum-stats">
                        <span class="stat">Relatórios: 156</span>
                        <span class="stat">Análises: 84</span>
                    </div>
                    <button class="quantum-button" onclick="viewReports()">Acessar Relatórios</button>
                </div>
                <div class="quantum-effect"></div>
            </div>

            <div class="quantum-card sessions-card">
                <div class="card-quantum-icon">
                    <svg viewBox="0 0 24 24" class="quantum-svg">
                        <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2zm-7 5h5v5h-5z" fill="currentColor"/>
                    </svg>
                </div>
                <div class="card-quantum-content">
                    <div class="card-title">Próximas Sessões</div>
                    <p>Veja suas próximas sessões agendadas.</p>
                    <div class="quantum-stats">
                        <span class="stat">Hoje: 5</span>
                        <span class="stat">Semana: 23</span>
                    </div>
                    <button class="quantum-button" onclick="viewSchedule()">Ver Agenda</button>
                </div>
                <div class="quantum-effect"></div>
            </div>

            <div class="quantum-card addons-card">
                <div class="card-quantum-icon">
                    <svg viewBox="0 0 24 24" class="quantum-svg">
                        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" fill="currentColor"/>
                    </svg>
                </div>
                <div class="card-quantum-content">
                    <div class="card-title">Configurações de Addons</div>
                    <p>Gerencie e configure addons adicionais.</p>
                    <div class="quantum-stats">
                        <span class="stat">Instalados: 6</span>
                        <span class="stat">Ativos: 4</span>
                    </div>
                    <button class="quantum-button" onclick="window.location.href='/wp-admin/admin.php?page=therapists_addons'">Gerenciar Addons</button>
                </div>
                <div class="quantum-effect"></div>
            </div>
        </div>
    </div>

    <style>
        .dashboard-header {
            text-align: center;
            margin-bottom: 2em;
            position: relative;
        }

        .quantum-text {
            background: linear-gradient(135deg, #00c9fe, #4facfe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: bold;
        }

        .energy-bar {
            height: 4px;
            background: linear-gradient(90deg, #00c9fe, #4facfe);
            width: 200px;
            margin: 1em auto;
            border-radius: 2px;
            position: relative;
            overflow: hidden;
        }

        .energy-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            animation: energyFlow 2s linear infinite;
        }

        @keyframes energyFlow {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .dashboard-quantum-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2em;
            max-width: 1200px;
            margin: 2em auto;
            padding: 0 1em;
        }

        .quantum-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 1.5em;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .quantum-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .card-quantum-icon {
            width: 50px;
            height: 50px;
            margin-bottom: 1em;
            position: relative;
        }

        .quantum-svg {
            width: 100%;
            height: 100%;
            color: #4facfe;
        }

        .card-quantum-content {
            position: relative;
            z-index: 2;
        }

        .card-title {
            font-size: 1.4em;
            font-weight: bold;
            margin-bottom: 0.5em;
            background: linear-gradient(135deg, #00c9fe, #4facfe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .quantum-stats {
            display: flex;
            justify-content: space-between;
            margin: 1em 0;
            font-size: 0.9em;
            color: #666;
        }

        .stat {
            background: rgba(79, 172, 254, 0.1);
            padding: 0.3em 0.8em;
            border-radius: 15px;
        }
		.quantum-button {
            background: linear-gradient(135deg, #00c9fe, #4facfe);
            color: white;
            padding: 0.8em;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .quantum-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 201, 254, 0.3);
        }

        .quantum-button::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                transparent,
                rgba(255, 255, 255, 0.2),
                transparent
            );
            transform: rotate(45deg);
            transition: all 0.3s ease;
        }

        .quantum-button:hover::after {
            animation: quantumGlow 1.5s infinite;
        }

        .quantum-effect {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(0, 201, 254, 0.05), rgba(79, 172, 254, 0.05));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .quantum-card:hover .quantum-effect {
            opacity: 1;
        }

        @keyframes quantumGlow {
            0% { transform: rotate(45deg) translateX(-100%); }
            100% { transform: rotate(45deg) translateX(100%); }
        }

        @media (max-width: 768px) {
            .dashboard-quantum-container {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        function viewPatients() {
            // Manter a funcionalidade existente
            console.log('Viewing patients...');
        }

        function viewReports() {
            // Manter a funcionalidade existente
            console.log('Viewing reports...');
        }

        function viewSchedule() {
            // Manter a funcionalidade existente
            console.log('Viewing schedule...');
        }
    </script>
    <?php
}

// Página de configuração de Addons
function therapists_addons_menu() {
    add_submenu_page(
        'therapists_dashboard',
        '5D Therapists Addons',
        'Addons',
        'manage_options',
        'therapists_addons',
        'therapists_addons_page'
    );
}
add_action('admin_menu', 'therapists_addons_menu');

// Conteúdo da página de Addons
function therapists_addons_page() {
    ?>
    <div class="wrap">
        <div class="dashboard-header">
            <h1><span class="quantum-text">5D Therapists</span> Addons</h1>
            <div class="energy-bar"></div>
        </div>
        <div id="addons-container" class="dashboard-quantum-container">
            <div class="quantum-card">
                <div class="card-quantum-icon">
                    <svg viewBox="0 0 24 24" class="quantum-svg">
                        <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10z" fill="currentColor"/>
                    </svg>
                </div>
                <div class="card-quantum-content">
                    <div class="card-title">Sistema de Agendamento</div>
                    <p>Gerenciamento completo de consultas e horários.</p>
                    <div class="quantum-stats">
                        <span class="stat">Status: Ativo</span>
                    </div>
                    <button class="quantum-button" onclick="activateAddon('scheduling')">Configurar</button>
                </div>
                <div class="quantum-effect"></div>
            </div>

            <div class="quantum-card">
                <div class="card-quantum-icon">
                    <svg viewBox="0 0 24 24" class="quantum-svg">
                        <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z" fill="currentColor"/>
                    </svg>
                </div>
                <div class="card-quantum-content">
                    <div class="card-title">Prontuário Digital</div>
                    <p>Sistema de prontuário eletrônico para pacientes.</p>
                    <div class="quantum-stats">
                        <span class="stat">Status: Instalado</span>
                    </div>
                    <button class="quantum-button" onclick="activateAddon('digital_record')">Configurar</button>
                </div>
                <div class="quantum-effect"></div>
            </div>

            <div class="quantum-card">
                <div class="card-quantum-icon">
                    <svg viewBox="0 0 24 24" class="quantum-svg">
                        <path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z" fill="currentColor"/>
                    </svg>
                </div>
                <div class="card-quantum-content">
                    <div class="card-title">Sistema de Pagamentos</div>
                    <p>Integração com métodos de pagamento.</p>
                    <div class="quantum-stats">
                        <span class="stat">Status: Disponível</span>
                    </div>
                    <button class="quantum-button" onclick="activateAddon('payments')">Configurar</button>
                </div>
                <div class="quantum-effect"></div>
            </div>

            <div class="quantum-card">
                <div class="card-quantum-icon">
                    <svg viewBox="0 0 24 24" class="quantum-svg">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z" fill="currentColor"/>
                        <path d="M7 12h2v5H7zm4-3h2v8h-2zm4-3h2v11h-2z" fill="currentColor"/>
                    </svg>
                </div>
                <div class="card-quantum-content">
                    <div class="card-title">Relatórios e Análises</div>
                    <p>Dashboard com relatórios avançados.</p>
                    <div class="quantum-stats">
                        <span class="stat">Status: Disponível</span>
                    </div>
                    <button class="quantum-button" onclick="activateAddon('reports')">Configurar</button>
                </div>
                <div class="quantum-effect"></div>
            </div>
        </div>
    </div>

    <script>
        function activateAddon(addon) {
            console.log('Activating addon:', addon);
            // Implementar lógica de ativação do addon
        }
    </script>
    <?php
}

?>
// Função de exemplo para análise do Korbit
function testarKorbitAI() {
   function testarKorbitAI() {
    $resultado = [
        'status' => '✅ Sucesso',
        'análise' => 'Nenhum bloqueio energético detectado.',
        'tempo' => '⏱️ 0.42s',
        'intensidade_quântica' => '💫 97.2%'
    ];

    echo '<div style="padding:20px; background:#e8f5e9; border-left: 5px solid #43a047;">';
    echo '<h3>🧬 Resultado da Análise Korbit AI</h3>';
    echo '<ul>';
    foreach ($resultado as $chave => $valor) {
        echo '<li><strong>' . ucfirst($chave) . ':</strong> ' . $valor . '</li>';
    }
    echo '</ul>';
    echo '</div>';
}

