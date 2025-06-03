<?php
/*
Plugin Name: 5D Therapists Addons
Description: Plugin para adicionar funcionalidades extras ao app 5D Therapists, como redirecionamento de páginas, botões de acesso, edições fáceis via frontend, agendamento de sessões, prontuário digital e sistema de pagamentos, além de funcionalidades quânticas avançadas.
Version: 1.7
Author: Mestre Ricardo
*/

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// get_safe_redirect_url
function get_safe_redirect_url($type, $path) {
    if ($type === 'admin') {
        return esc_url(admin_url($path));
    } elseif ($type === 'page') {
        $page = get_page_by_path($path);
        return $page ? esc_url(get_permalink($page->ID)) : admin_url('admin.php?page=therapists-dashboard');
    }
    return '#';
}

// Função para criar páginas automaticamente ao ativar o plugin
function therapists_addons_create_pages() {
    $pages = [
        'quantum-analysis' => [
            'title' => 'Análise Energética Quântica',
            'content' => '[quantum_analysis]'
        ],
        'frequency-report' => [
            'title' => 'Relatórios de Frequência Quântica',
            'content' => '[frequency_report]'
        ],
        'sensor-analysis' => [
            'title' => 'Análise por Sensores',
            'content' => '[sensor_analysis]'
        ],
        'iris-skin-analysis' => [
            'title' => 'Leitura de Pele e Íris',
            'content' => '[iris_skin_analysis]'
        ]
    ];

    foreach ($pages as $slug => $page) {
        if (null === get_page_by_path($slug)) {
            wp_insert_post([
                'post_title'   => $page['title'],
                'post_content' => $page['content'],
                'post_name'    => $slug,
                'post_status'  => 'publish',
                'post_type'    => 'page'
            ]);
        }
    }
}
register_activation_hook(__FILE__, 'therapists_addons_create_pages');

// Função para criar tabelas no banco de dados
function therapists_addons_create_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Tabela para resultados de análises energéticas
    $table_name = $wpdb->prefix . 'quantum_analysis';
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        patient_id mediumint(9) NOT NULL,
        analysis_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        analysis_result longtext NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    // Outras tabelas
    $table_names = [
        'frequency_reports' => "CREATE TABLE {$wpdb->prefix}frequency_reports (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            patient_id mediumint(9) NOT NULL,
            report_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            report_data longtext NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;",
        'sensor_analysis' => "CREATE TABLE {$wpdb->prefix}sensor_analysis (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            patient_id mediumint(9) NOT NULL,
            analysis_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            sensor_data longtext NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;",
        'iris_skin_analysis' => "CREATE TABLE {$wpdb->prefix}iris_skin_analysis (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            patient_id mediumint(9) NOT NULL,
            analysis_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            iris_data longtext NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;"
    ];

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    foreach ($table_names as $name => $table_sql) {
        dbDelta($table_sql);
    }
}
register_activation_hook(__FILE__, 'therapists_addons_create_tables');

// Criar tabela de pacientes
function create_patients_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    $table_name = $wpdb->prefix . 'quantum_patients';
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        patient_uuid VARCHAR(36) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE,
        birth_date DATE,
        phone VARCHAR(20),
        registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_update DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        status VARCHAR(20) DEFAULT 'active',
        PRIMARY KEY (id),
        INDEX idx_patient_uuid (patient_uuid),
        INDEX idx_email (email)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'create_patients_table');

// Gerar UUID único
function generate_patient_uuid() {
    if (function_exists('com_create_guid')) {
        return trim(com_create_guid(), '{}');
    }
    return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', 
        mt_rand(0, 65535), mt_rand(0, 65535), 
        mt_rand(0, 65535), mt_rand(16384, 20479), 
        mt_rand(32768, 49151), mt_rand(0, 65535), 
        mt_rand(0, 65535), mt_rand(0, 65535));
}

// Registrar novo paciente
function register_new_patient($patient_data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'quantum_patients';
    
    // Validar email duplicado
    if (!empty($patient_data['email'])) {
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE email = %s",
            $patient_data['email']
        ));
        if ($existing) {
            return ['error' => 'Email já cadastrado'];
        }
    }
    
    $patient_data['patient_uuid'] = generate_patient_uuid();
    
    $result = $wpdb->insert($table_name, $patient_data);
    
    if ($result === false) {
        return ['error' => 'Erro ao registrar paciente'];
    }
    
    return [
        'success' => true,
        'patient_id' => $wpdb->insert_id,
        'patient_uuid' => $patient_data['patient_uuid']
    ];
}

add_shortcode('patient_registration', 'patient_registration_form_shortcode');

// Handler AJAX para registro
add_action('wp_ajax_register_patient', 'handle_patient_registration');
add_action('wp_ajax_nopriv_register_patient', 'handle_patient_registration');

function handle_patient_registration() {
    if (!check_ajax_referer('patient_registration_nonce', 'nonce', false)) {
        wp_send_json_error('Nonce inválido');
    }
    
    $patient_data = [
        'name' => sanitize_text_field($_POST['name']),
        'email' => sanitize_email($_POST['email']),
        'birth_date' => sanitize_text_field($_POST['birth_date']),
        'phone' => sanitize_text_field($_POST['phone'])
    ];
    
    $result = register_new_patient($patient_data);
    
    if (isset($result['error'])) {
        wp_send_json_error($result['error']);
    }
    
    wp_send_json_success($result);
}

// Função para salvar resultados de análise energética
function save_quantum_analysis_result($patient_id, $analysis_result) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'quantum_analysis';
    $wpdb->insert(
        $table_name,
        [
            'patient_id' => $patient_id,
            'analysis_date' => current_time('mysql'),
            'analysis_result' => $analysis_result,
        ]
    );
}

// Shortcode para Análise Energética Quântica
function quantum_analysis_shortcode() {
    ob_start();
    ?>
    <div class="quantum-analysis-container">
        <h2>Iniciar Análise Energética Quântica</h2>
        <p>Realize a análise dos campos energéticos dos pacientes.</p>
        <input type="number" id="patient_id" placeholder="ID do Paciente" />
        <button onclick="iniciarAnaliseEnergetica()">Iniciar Análise</button>
    </div>
    <script>
        function iniciarAnaliseEnergetica() {
            const patientId = document.getElementById('patient_id').value;
            const nonce = '<?php echo wp_create_nonce("quantum_analysis_nonce"); ?>';
            
            if (!patientId) {
                alert('Por favor, insira o ID do paciente.');
                return;
            }

            // Mostrar loading
            const button = event.target;
            const originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = 'Analisando...';

            const formData = new FormData();
            formData.append('action', 'quantum_analysis');
            formData.append('nonce', nonce);
            formData.append('patient_id', patientId);

            fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayAnalysisResults(data.data);
                } else {
                    throw new Error(data.data.message);
                }
            })
            .catch(error => {
                alert('Erro: ' + error.message);
            })
            .finally(() => {
                button.disabled = false;
                button.innerHTML = originalText;
            });
        }

        function displayAnalysisResults(results) {
            const container = document.createElement('div');
            container.className = 'analysis-results';
            container.innerHTML = 
                <h3>Resultados da Análise</h3>
                <div class="result-content">${results.analysis}</div>
                <div class="result-meta">
                    Análise realizada em: ${results.timestamp}
                    ID do Paciente: ${results.patient_id}
                </div>
            ;

            const existingResults = document.querySelector('.analysis-results');
            if (existingResults) {
                existingResults.remove();
            }

            document.querySelector('.quantum-analysis-container').appendChild(container);
        }
    </script>
    <?php
    return ob_get_clean();
	}
add_shortcode('quantum_analysis', 'quantum_analysis_shortcode');

function enviarAnamnese() {
        alert("Anamnese enviada com sucesso!");

// Função para tratar a análise energética quântica via AJAX
function quantum_analysis_handler() {
    // Verificar nonce para segurança
    check_ajax_referer('quantum_analysis_nonce', 'nonce');

    // Verificar API key
    $api_key = get_option('openai_api_key');
    if (empty($api_key)) {
        wp_send_json_error([
            'message' => 'Chave da API OpenAI não configurada.',
            'type' => 'api_key_missing'
        ]);
        return;
    }

    // Validar patient_id
    $patient_id = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;
    if (!$patient_id) {
        wp_send_json_error([
            'message' => 'ID do paciente inválido.',
            'type' => 'invalid_patient'
        ]);
        return;
    }

    // Configuração da API OpenAI
    $endpoint = 'https://api.openai.com/v1/completions';
    $prompt = "Realize uma análise energética quântica para o paciente ID: {$patient_id}. " . 
              "Inclua: níveis de energia, harmonia dos chakras, ressonância vibracional e recomendações.";

    $args = [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ],
        'body' => wp_json_encode([
            'model' => 'text-davinci-003',
            'prompt' => $prompt,
            'max_tokens' => 500,
            'temperature' => 0.7
        ]),
        'method' => 'POST',
        'timeout' => 30
    ];

    try {
        $response = wp_remote_post($endpoint, $args);

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (!isset($result['choices'][0]['text'])) {
            throw new Exception('Resposta inválida da API');
        }

        $analysis_result = $result['choices'][0]['text'];

        // Salvar no banco de dados
        save_quantum_analysis_result($patient_id, $analysis_result);

        // Formatar resposta
        $formatted_result = [
            'patient_id' => $patient_id,
            'analysis' => $analysis_result,
            'timestamp' => current_time('mysql'),
            'status' => 'success'
        ];

        wp_send_json_success($formatted_result);

    } catch (Exception $e) {
        wp_send_json_error([
            'message' => 'Erro na análise: ' . $e->getMessage(),
            'type' => 'api_error'
        ]);
    }
}
add_action('wp_ajax_quantum_analysis', 'quantum_analysis_handler');
add_action('wp_ajax_nopriv_quantum_analysis', 'quantum_analysis_handler');

// Página de configurações para chave da API OpenAI
function therapists_addons_settings_page() {
    add_menu_page(
        'Configurações 5D Therapists',
        'Configurações 5D Therapists',
        'manage_options',
        'therapists-addons-settings',
        'therapists_addons_settings_page_html',
        'dashicons-admin-generic',
        20
    );
}

    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Você não tem permissão para realizar esta ação.');
    }

add_action('admin_menu', 'therapists_addons_settings_page');

function therapists_addons_settings_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['openai_api_key'])) {
        update_option('openai_api_key', sanitize_text_field($_POST['openai_api_key']));
        echo '<div class="updated"><p>Chave da API OpenAI atualizada com sucesso.</p></div>';
    }

    $api_key = get_option('openai_api_key', '');
    ?>
    <div class="wrap">
        <h1>Configurações 5D Therapists</h1>
        <form method="post">
            <label for="openai_api_key">Chave da API OpenAI:</label>
            <input type="text" name="openai_api_key" id="openai_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text">
            <?php submit_button('Salvar Chave da API'); ?>
        </form>
    </div>
    <?php
}

function render_quantum_measurement() {
    ?>
    <div class="quantum-measurement-container">
        <div class="quantum-visualizer"></div>
        
        <div class="quantum-readings">
            <div class="reading-card">
                <h3>Frequência Base</h3>
                <div class="quantum-frequency-value">528.00 Hz</div>
                <div class="quantum-wave-indicator"></div>
            </div>
            
            <div class="reading-card">
                <h3>Harmonia Quântica</h3>
                <div class="quantum-harmony-meter">
                    <div class="harmony-fill"></div>
                </div>
                <div class="harmony-value">96%</div>
            </div>
            
            <div class="reading-card">
                <h3>Campo Energético</h3>
                <div class="energy-field-visualizer">
                    <div class="field-strength"></div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function render_quantum_frequency() {

}
    ?>
    <div class="quantum-measurement">
        <div class="frequency-display">
            <div class="frequency-meter">
                <div class="frequency-line" id="frequencyLine"></div>
            </div>
            <div class="frequency-value">
                <span id="frequencyValue">528</span> Hz
            </div>
        </div>
        <div class="quantum-stats">
            <div class="stat-item">
                <label>Harmonia</label>
                <span id="harmonyLevel">96%</span>
            </div>
            <div class="stat-item">
                <label>Coerência</label>
                <span id="coherenceLevel">92%</span>
            </div>
            <div class="stat-item">
                <label>Ressonância</label>
                <span id="resonanceLevel">94%</span>
            </div>
        </div>
    </div>
    
function initQuantumEffects() {
    // Animação de onda quântica
    const frequencyLine = document.getElementById('frequencyLine');
    let frequency = 528; // Frequência base
    
    function updateFrequency() {
        // Simular variações quânticas
        frequency += (Math.random() - 0.5) * 2;
        
        // Atualizar display
        document.getElementById('frequencyValue').textContent = 
            frequency.toFixed(2);
            
        // Ajustar animação
        frequencyLine.style.animation = 
            frequencyPulse ${1000/frequency}s infinite ease-in-out;
    }
    
    setInterval(updateFrequency, 1000);
}

// Função para gerar campo quântico visual
function generateQuantumField(container) {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    
    canvas.width = container.offsetWidth;
    canvas.height = container.offsetHeight;
    
    function drawField() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Desenhar partículas quânticas
        for(let i = 0; i < 100; i++) {
            const x = Math.random() * canvas.width;
            const y = Math.random() * canvas.height;
            const radius = Math.random() * 2;
            
            ctx.beginPath();
            ctx.arc(x, y, radius, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(79, 172, 254, 0.5)';
            ctx.fill();
        }
        
        requestAnimationFrame(drawField);
    }
    
    drawField();
    container.appendChild(canvas);
}
function render_chakra_analysis() {
    ?>
    <div class="chakra-analyzer">
        <div class="chakra-display">
            <?php
            $chakras = [
                'crown' => ['color' => '#9B59B6', 'freq' => 963],
                'third-eye' => ['color' => '#5B48A2', 'freq' => 852],
                'throat' => ['color' => '#3498DB', 'freq' => 741],
                'heart' => ['color' => '#2ECC71', 'freq' => 639],
                'solar' => ['color' => '#F1C40F', 'freq' => 528],
                'sacral' => ['color' => '#E67E22', 'freq' => 417],
                'root' => ['color' => '#E74C3C', 'freq' => 396]
            ];
            
            foreach($chakras as $name => $data) {
                ?>
                <div class="chakra-point" 
                     style="background: <?php echo $data['color']; ?>">
                    <div class="chakra-wave"></div>
                    <span class="chakra-freq"><?php echo $data['freq']; ?> Hz</span>
                </div>
                <?php
            }
            ?>
        </div>
    </div>

}

.chakra-analyzer {
    background: rgba(255, 255, 255, 0.9);
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.chakra-point {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    position: relative;
    margin: 10px auto;
}

.chakra-wave {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    border-radius: 50%;
    animation: chakraPulse 2s infinite;
}

@keyframes chakraPulse {
    0% { transform: scale(1); opacity: 0.8; }
    50% { transform: scale(1.5); opacity: 0; }
    100% { transform: scale(1); opacity: 0.8; }
}

// Corrigir o formulário de registro
function patient_registration_form_shortcode() {
    ob_start(); ?>
    <div class="patient-registration-form">
        <form id="newPatientForm">
            <?php wp_nonce_field('patient_registration_nonce'); ?>
            <input type="text" name="name" required placeholder="Nome completo">
            <input type="email" name="email" required placeholder="Email">
            <input type="date" name="birth_date" required>
            <input type="tel" name="phone" placeholder="Telefone">
            <button type="submit">Registrar Paciente</button>
        </form>
        <div id="registrationResult"></div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#newPatientForm').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            formData.append('action', 'register_patient');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if(response.success) {
                        $('#registrationResult').html('Paciente registrado. ID: ' + response.data.patient_uuid);
                    } else {
                        $('#registrationResult').html('Erro: ' + response.data.error);
                    }
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_action('wp_footer', function() {
    ?>
    <script>
    var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
    </script>
    <?php
});


// Shortcode para Relatórios de Frequência Quântica
function frequency_report_shortcode() {
    ob_start();
    ?>
    <div class="frequency-report-container">
        <h2>Gerar Relatórios de Frequência Quântica</h2>
        <p>Gerar relatórios de frequência energética dos pacientes.</p>
        <button onclick="alert('Gerando relatórios de frequência quântica...')">Gerar Relatórios</button>;
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('frequency_report', 'frequency_report_shortcode');

// Shortcode para Análise por Sensores
function sensor_analysis_shortcode() {
    ob_start();
    ?>
    <div class="sensor-analysis-container">
        <h2>Análise por Sensores</h2>
        <p>Processar dados dos sensores para análise energética.</p>
        <button onclick="alert('Iniciando análise por sensores...')">Ver Análise</button>;
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('sensor_analysis', 'sensor_analysis_shortcode');

// Shortcode para Leitura de Pele e Íris
function iris_skin_analysis_shortcode() {
    ob_start();
    ?>
    <div class="iris-skin-analysis-container">
        <h2>Leitura de Pele e Íris</h2>
        <p>Realizar análise de pele e íris dos pacientes.</p>
        <button onclick="alert('Iniciando leitura de pele e íris...')">Iniciar Leitura</button>;
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('iris_skin_analysis', 'iris_skin_analysis_shortcode');

// Função para exibir todos os addons no dashboard
function therapists_addons_dashboard() {
    add_menu_page(
        '5D Therapists Dashboard',
        '5D Therapists Dashboard',
        'manage_options',
        'therapists-dashboard',
        'therapists_addons_dashboard_html',
        'dashicons-chart-bar',
        5
    );
}
add_action('admin_menu', 'therapists_addons_dashboard');

function therapists_addons_dashboard_html() {
    ?>
<div class="wrap">
    <h1>5D Therapists Dashboard</h1>
    <div class="dashboard-container">
        <div class="dashboard-card">
            <h2>Pacientes</h2>
            <p>Visualize e gerencie seus pacientes.</p>
            <button onclick="location.href='<?php echo esc_url(admin_url('edit.php?post_type=patient')); ?>'">Ver Pacientes</button>
        </div>

        <div class="dashboard-card">
            <h2>Relatórios</h2>
            <p>Acesse relatórios de progresso e análises.</p>
            <button onclick="location.href='<?php echo esc_url(admin_url('edit.php?post_type=report')); ?>'">Acessar Relatórios</button>
        </div>

        <div class="dashboard-card">
            <h2>Próximas Sessões</h2>
            <p>Veja suas próximas sessões agendadas.</p>
            <button onclick="location.href='<?php echo esc_url(admin_url('edit.php?post_type=session')); ?>'">Ver Agenda</button>
        </div>

        <div class="dashboard-card">
            <h2>Configurações de Addons</h2>
            <p>Gerencie e configure addons adicionais.</p>
            <button onclick="location.href='<?php echo esc_url(admin_url('admin.php?page=therapists-addons-settings')); ?>'">Gerenciar Addons</button>
        </div>

        <div class="dashboard-card">
            <h2>Análise Energética Quântica</h2>
            <p>Realize a análise dos campos energéticos dos pacientes.</p>
            <button onclick="location.href='<?php echo esc_url(home_url('/quantum-analysis/')); ?>'">Iniciar Análise</button>
        </div>

        <div class="dashboard-card">
            <h2>Relatórios de Frequência Quântica</h2>
            <p>Gerar relatórios de frequência energética.</p>
            <button onclick="location.href='<?php echo esc_url(home_url('/frequency-report/')); ?>'">Gerar Relatórios</button>
        </div>

        <div class="dashboard-card">
            <h2>Análise por Sensores</h2>
            <p>Processar dados dos sensores para análise energética.</p>
            <button onclick="location.href='<?php echo esc_url(home_url('/sensor-analysis/')); ?>'">Ver Análise</button>
        </div>

        <div class="dashboard-card">
            <h2>Leitura de Pele e Íris</h2>
            <p>Realizar análise de pele e íris dos pacientes.</p>
            <button onclick="location.href='<?php echo esc_url(home_url('/iris-skin-analysis/')); ?>'">Iniciar Leitura</button>
        </div>
    </div>
</div>

.quantum-measurement-container {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(5px);
}

.quantum-visualizer {
    width: 100%;
    height: 300px;
    position: relative;
    margin-bottom: 2rem;
    border-radius: 15px;
    overflow: hidden;
    background: rgba(0, 0, 0, 0.05);
}

.quantum-readings {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.reading-card {
    background: white;
    padding: 1.5rem;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    text-align: center;
    position: relative;
    overflow: hidden;
}

.reading-card h3 {
    color: #2c3e50;
    margin-bottom: 1rem;
}

.quantum-frequency-value {
    font-size: 2rem;
    font-weight: bold;
    background: linear-gradient(135deg, #00c9fe, #4facfe);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin: 1rem 0;
}

.quantum-wave-indicator {
    height: 4px;
    background: linear-gradient(90deg, #00c9fe, #4facfe);
    position: relative;
    overflow: hidden;
}

.quantum-wave-indicator::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 200%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
    animation: waveFlow 2s linear infinite;
}

@keyframes waveFlow {
    0% { transform: translateX(0); }
    100% { transform: translateX(100%); }
}

.harmony-meter {
    height: 150px;
    width: 150px;
    border-radius: 50%;
    margin: 0 auto;
    position: relative;
    background: conic-gradient(from 0deg, #00c9fe 96%, #eee 0%);
}

.harmony-value {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 1.5rem;
    font-weight: bold;
}

    <style>
        .dashboard-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .dashboard-card {
            background: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            width: calc(33.333% - 20px);
            text-align: center;
        }
        .dashboard-card h2 {
            color: #0073aa;
        }
        .dashboard-card button {
            background: #0073aa;
            color: #ffffff;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .dashboard-card button:hover {
            background: #005177;
        }
    </style>
    <?php
}

// Inclusão dos templates quânticos
function include_quantum_templates() {
    require_once plugin_dir_path(__FILE__) . 'templates/quantum-measurement.php';
}
add_action('init', 'include_quantum_templates');

// Criação de shortcode para usar o template
function quantum_measurement_shortcode() {
    ob_start();
    render_quantum_measurement(); // Certifique-se de que essa função está definida corretamente
    return ob_get_clean();
}
add_shortcode('quantum_measurement', 'quantum_measurement_shortcode');

?>

<!-- Estrutura HTML -->
<div class="quantum-measurement-container">
    <!-- Visualizador Quântico -->
    <div class="quantum-visualizer"></div>

    <!-- Leituras Quânticas -->
    <div class="quantum-readings">
        <!-- Frequência -->
        <div class="reading-card">
            <h3>Frequência Base:</h3>
            <div class="quantum-frequency-value">528.00 Hz</div>
            <div class="quantum-wave-indicator"></div>
        </div>

        <!-- Harmonia -->
        <div class="reading-card">
            <h3>Harmonia Quântica:</h3>
            <div class="quantum-harmony-meter">
                <div class="harmony-fill"></div>
            </div>
            <div class="harmony-value">96%</div>
        </div>

        <!-- Campo Energético -->
        <div class="reading-card">
            <h3>Campo Energético:</h3>
            <div class="quantum-energy-level">Alto</div>
        </div>
    </div>
</div>

    </div>
    <?php
    return ob_get_clean();

function enqueue_quantum_effects() {
    wp_enqueue_style(
        'quantum-styles',
        plugins_url('assets/css/quantum-styles.css', __FILE__),
        array(),
        '1.0.0'
    );

    wp_enqueue_script(
        'quantum-effects',
        plugins_url('assets/js/quantum-effects.js', __FILE__),
        array(),
        '1.0.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'enqueue_quantum_effects');

function quantum_anamnesis_enqueue_styles() {
    wp_enqueue_style(
        'quantum-anamnesis-css',
        plugin_dir_url(__FILE__) . 'assets/css/quantum-anamnesis.css',
        array(),
        '1.0',
        'all'
    );
}
add_action('wp_enqueue_scripts', 'quantum_anamnesis_enqueue_styles');

function quantum_anamnesis_shortcode() {
    ob_start();
    ?>
    <div class="quantum-anamnesis-container">
        <h2>Formulário de Anamnese Quântica</h2>
        <form id="quantum-anamnesis-form">
            <label for="patient_name">Nome do Paciente:</label>
            <input type="text" id="patient_name" name="patient_name" required>

            <label for="birth_date">Data de Nascimento:</label>
            <input type="date" id="birth_date" name="birth_date" required>

            <label for="energetic_block">Você sente algum bloqueio energético?</label>
            <select id="energetic_block" name="energetic_block" required>
                <option value="">Selecione...</option>
                <option value="sim">Sim</option>
                <option value="nao">Não</option>
            </select>

            <button type="button" onclick="enviarAnamnese()">Enviar Anamnese</button>
        </form>
    </div>
    <script>
    function enviarAnamnese() {
        alert('Anamnese enviada com sucesso!');
    }
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('quantum_anamnesis', 'quantum_anamnesis_shortcode');
?>
