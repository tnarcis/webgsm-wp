<?php
/**
 * Plugin Name: WebGSM Setup Wizard v2
 * Description: Creează structura finală cu 5 taburi: Parts, Tools, Accessories, Devices, Services
 * Version: 2.0.0
 * Author: WebGSM
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

class WebGSM_Setup_Wizard_V2 {
    
    private static $instance = null;
    
    // ===========================================
    // STRUCTURA CATEGORIILOR - 5 TABURI (Piese are 3 nivele: Piese > Piese iPhone > Ecrane)
    // ===========================================
    private $categories = [
        'Piese' => [
            'slug' => 'piese',
            'description' => 'Piese și componente pentru telefoane',
            'children' => [
                'Piese iPhone' => [
                    'slug' => 'piese-iphone',
                    'children' => [
                        'Ecrane' => 'ecrane',
                        'Baterii' => 'baterii',
                        'Camere' => 'camere',
                        'Mufe Încărcare' => 'mufe-incarcare',
                        'Flexuri' => 'flexuri',
                        'Difuzoare' => 'difuzoare',
                        'Carcase' => 'carcase',
                    ]
                ],
                'Piese Samsung' => [
                    'slug' => 'piese-samsung',
                    'children' => [
                        'Ecrane' => 'ecrane',
                        'Baterii' => 'baterii',
                        'Camere' => 'camere',
                        'Mufe Încărcare' => 'mufe-incarcare',
                        'Flexuri' => 'flexuri',
                    ]
                ],
                'Piese Huawei' => [
                    'slug' => 'piese-huawei',
                    'children' => [
                        'Ecrane' => 'ecrane',
                        'Baterii' => 'baterii',
                        'Camere' => 'camere',
                    ]
                ],
                'Piese Xiaomi' => [
                    'slug' => 'piese-xiaomi',
                    'children' => [
                        'Ecrane' => 'ecrane',
                        'Baterii' => 'baterii',
                        'Camere' => 'camere',
                    ]
                ],
            ]
        ],
        'Unelte' => [
            'slug' => 'unelte',
            'description' => 'Unelte și echipamente pentru service',
            'children' => [
                'Șurubelnițe' => 'surubelnite',
                'Pensete' => 'pensete',
                'Stații Lipit' => 'statii-lipit',
                'Separatoare Ecrane' => 'separatoare-ecrane',
                'Microscoape' => 'microscoape',
                'Programatoare' => 'programatoare',
                'Kituri Complete' => 'kituri-complete',
            ]
        ],
        'Accesorii' => [
            'slug' => 'accesorii',
            'description' => 'Accesorii și consumabile',
            'children' => [
                'Huse & Carcase' => 'huse-carcase',
                'Folii Protecție' => 'folii-protectie',
                'Cabluri & Încărcătoare' => 'cabluri-incarcatoare',
                'Adezivi & Consumabile' => 'adezivi-consumabile',
            ]
        ],
        'Dispozitive' => [
            'slug' => 'dispozitive',
            'description' => 'Telefoane și tablete',
            'children' => [
                'Telefoane Folosite' => 'telefoane-folosite',
                'Telefoane Refurbished' => 'telefoane-refurbished',
                'Tablete' => 'tablete',
                'Smartwatch' => 'smartwatch',
            ]
        ],
        'Servicii' => [
            'slug' => 'servicii',
            'description' => 'Servicii și suport',
            'children' => [
                'Reparații' => 'reparatii',
                'Training' => 'training',
                'Buy-back' => 'buy-back',
            ]
        ],
    ];
    
    // ===========================================
    // ATRIBUTE PENTRU FILTRARE
    // ===========================================
    private $attributes = [
        'Model' => [
            'slug' => 'model',
            'terms' => [
                // iPhone
                'iPhone 16 Pro Max', 'iPhone 16 Pro', 'iPhone 16 Plus', 'iPhone 16',
                'iPhone 15 Pro Max', 'iPhone 15 Pro', 'iPhone 15 Plus', 'iPhone 15',
                'iPhone 14 Pro Max', 'iPhone 14 Pro', 'iPhone 14 Plus', 'iPhone 14',
                'iPhone 13 Pro Max', 'iPhone 13 Pro', 'iPhone 13', 'iPhone 13 Mini',
                'iPhone 12 Pro Max', 'iPhone 12 Pro', 'iPhone 12', 'iPhone 12 Mini',
                'iPhone 11 Pro Max', 'iPhone 11 Pro', 'iPhone 11',
                'iPhone XS Max', 'iPhone XS', 'iPhone XR', 'iPhone X',
                'iPhone SE 2022', 'iPhone SE 2020',
                'iPhone 8 Plus', 'iPhone 8', 'iPhone 7 Plus', 'iPhone 7',
                // Samsung Galaxy S
                'Galaxy S24 Ultra', 'Galaxy S24+', 'Galaxy S24',
                'Galaxy S23 Ultra', 'Galaxy S23+', 'Galaxy S23', 'Galaxy S23 FE',
                'Galaxy S22 Ultra', 'Galaxy S22+', 'Galaxy S22',
                'Galaxy S21 Ultra', 'Galaxy S21+', 'Galaxy S21', 'Galaxy S21 FE',
                'Galaxy S20 Ultra', 'Galaxy S20+', 'Galaxy S20', 'Galaxy S20 FE',
                // Samsung Galaxy A
                'Galaxy A55', 'Galaxy A54', 'Galaxy A53', 'Galaxy A52', 'Galaxy A52s',
                'Galaxy A35', 'Galaxy A34', 'Galaxy A33',
                'Galaxy A25', 'Galaxy A24', 'Galaxy A23',
                'Galaxy A15', 'Galaxy A14', 'Galaxy A13',
                // Samsung Galaxy Z
                'Galaxy Z Fold 6', 'Galaxy Z Fold 5', 'Galaxy Z Fold 4', 'Galaxy Z Fold 3',
                'Galaxy Z Flip 6', 'Galaxy Z Flip 5', 'Galaxy Z Flip 4', 'Galaxy Z Flip 3',
                // Samsung Note
                'Galaxy Note 20 Ultra', 'Galaxy Note 20', 'Galaxy Note 10+', 'Galaxy Note 10',
                // Huawei
                'Huawei P60 Pro', 'Huawei P50 Pro', 'Huawei P40 Pro', 'Huawei P30 Pro', 'Huawei P30',
                'Huawei Mate 50 Pro', 'Huawei Mate 40 Pro', 'Huawei Mate 30 Pro',
                // Xiaomi
                'Xiaomi 14 Ultra', 'Xiaomi 14 Pro', 'Xiaomi 14',
                'Xiaomi 13 Ultra', 'Xiaomi 13 Pro', 'Xiaomi 13',
                'Redmi Note 13 Pro+', 'Redmi Note 13 Pro', 'Redmi Note 13',
                'Redmi Note 12 Pro+', 'Redmi Note 12 Pro', 'Redmi Note 12',
                'Poco X6 Pro', 'Poco X5 Pro', 'Poco F5',
                // OnePlus
                'OnePlus 12', 'OnePlus 11', 'OnePlus 10 Pro', 'OnePlus 9 Pro',
                // Google Pixel
                'Pixel 8 Pro', 'Pixel 8', 'Pixel 7 Pro', 'Pixel 7', 'Pixel 6 Pro', 'Pixel 6',
            ]
        ],
        
        'Calitate' => [
            'slug' => 'calitate',
            'terms' => [
                'Original Service Pack',
                'Premium OEM', 
                'OEM',
                'Aftermarket HQ',
                'Aftermarket',
                'Refurbished',
                'Pull (Dezmembrare)',
            ]
        ],
        
        'Brand Piesă' => [
            'slug' => 'brand-piesa',
            'terms' => [
                // Ecrane
                'JK Incell',
                'GX Hard OLED', 
                'GX Soft OLED',
                'ZY Incell',
                'RJ Incell',
                'Ampsentrix',
                'Youda',
                'HQ',
                'PCC',
                'Original Apple',
                'Original Samsung',
                // Baterii
                'Desay',
                'ATL',
                'Sunwoda',
            ]
        ],
        
        'Tehnologie Display' => [
            'slug' => 'tehnologie',
            'terms' => [
                'Soft OLED',
                'Hard OLED',
                'AMOLED',
                'Super AMOLED',
                'Incell LCD',
                'In-Cell',
                'LCD IPS',
                'LCD TFT',
                'Retina',
            ]
        ],
        
        'Brand Telefon' => [
            'slug' => 'brand-telefon',
            'terms' => [
                'Apple',
                'Samsung', 
                'Huawei',
                'Xiaomi',
                'OnePlus',
                'Google',
                'Oppo',
                'Motorola',
                'Sony',
                'LG',
                'Nokia',
            ]
        ],
        
        'Culoare' => [
            'slug' => 'culoare',
            'terms' => [
                'Negru',
                'Alb',
                'Auriu',
                'Argintiu',
                'Albastru',
                'Roșu',
                'Verde',
                'Mov',
                'Roz',
                'Gri',
            ]
        ],
    ];
    
    // ===========================================
    // STRUCTURĂ MENIU (cum apare în megamenu)
    // ===========================================
    private $menu_structure = [
        'Piese' => [
            'icon' => '🔧',
            'columns' => [
                'iPhone' => ['Ecrane', 'Baterii', 'Camere', 'Mufe Încărcare', 'Flexuri', 'Difuzoare', 'Carcase'],
                'Samsung' => ['Ecrane', 'Baterii', 'Camere', 'Mufe Încărcare', 'Flexuri'],
                'Huawei' => ['Ecrane', 'Baterii', 'Camere'],
                'Xiaomi' => ['Ecrane', 'Baterii', 'Camere'],
            ]
        ],
        'Unelte' => [
            'icon' => '🛠️',
            'columns' => [
                'Unelte' => ['Șurubelnițe', 'Pensete', 'Stații Lipit', 'Separatoare Ecrane', 'Microscoape', 'Programatoare', 'Kituri Complete'],
            ]
        ],
        'Accesorii' => [
            'icon' => '📦',
            'columns' => [
                'Accesorii' => ['Huse & Carcase', 'Folii Protecție', 'Cabluri & Încărcătoare', 'Adezivi & Consumabile'],
            ]
        ],
        'Dispozitive' => [
            'icon' => '📱',
            'simple' => true,
        ],
        'Servicii' => [
            'icon' => '⚡',
            'simple' => true,
        ],
    ];
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'admin_styles']);
        
        // AJAX handlers
        add_action('wp_ajax_webgsm_v2_create_categories', [$this, 'ajax_create_categories']);
        add_action('wp_ajax_webgsm_v2_create_attributes', [$this, 'ajax_create_attributes']);
        add_action('wp_ajax_webgsm_v2_create_menu', [$this, 'ajax_create_menu']);
        add_action('wp_ajax_webgsm_v2_setup_filters', [$this, 'ajax_setup_filters']);
        add_action('wp_ajax_webgsm_v2_reset', [$this, 'ajax_reset']);
        add_action('wp_ajax_webgsm_v2_cleanup', [$this, 'ajax_cleanup']);
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'WebGSM Setup v2',
            '🚀 WebGSM v2',
            'manage_options',
            'webgsm-setup-v2',
            [$this, 'render_admin_page'],
            'dashicons-admin-tools',
            29
        );
    }
    
    public function admin_styles($hook) {
        if ($hook !== 'toplevel_page_webgsm-setup-v2') return;
        
        wp_enqueue_script('jquery');
        ?>
        <style>
            .webgsm-wrap { max-width: 1000px; margin: 20px auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
            .webgsm-header { background: linear-gradient(135deg, #1e40af 0%, #7c3aed 100%); color: white; padding: 30px; border-radius: 16px; margin-bottom: 30px; }
            .webgsm-header h1 { margin: 0 0 8px 0; font-size: 28px; color: white; }
            .webgsm-header p { margin: 0; opacity: 0.9; }
            
            .webgsm-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
            @media (max-width: 900px) { .webgsm-grid { grid-template-columns: 1fr; } }
            
            .webgsm-card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #e5e7eb; }
            .webgsm-card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
            .webgsm-card-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
            .webgsm-card-icon.blue { background: #dbeafe; }
            .webgsm-card-icon.green { background: #d1fae5; }
            .webgsm-card-icon.purple { background: #ede9fe; }
            .webgsm-card-icon.orange { background: #ffedd5; }
            .webgsm-card-icon.red { background: #fee2e2; }
            
            .webgsm-card h3 { margin: 0; font-size: 16px; color: #1f2937; }
            .webgsm-card p { color: #6b7280; font-size: 13px; margin: 0 0 16px 0; }
            
            .webgsm-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
            .webgsm-btn-primary { background: #2563eb; color: white; }
            .webgsm-btn-primary:hover { background: #1d4ed8; transform: translateY(-1px); }
            .webgsm-btn-success { background: #059669; color: white; }
            .webgsm-btn-danger { background: #dc2626; color: white; }
            .webgsm-btn-danger:hover { background: #b91c1c; }
            .webgsm-btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none !important; }
            
            .webgsm-status { margin-top: 12px; padding: 10px 14px; border-radius: 8px; font-size: 13px; display: none; }
            .webgsm-status.show { display: block; }
            .webgsm-status.loading { background: #dbeafe; color: #1e40af; }
            .webgsm-status.success { background: #d1fae5; color: #065f46; }
            .webgsm-status.error { background: #fee2e2; color: #991b1b; }
            
            .webgsm-preview { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin: 12px 0; font-size: 11px; font-family: monospace; max-height: 150px; overflow-y: auto; white-space: pre; }
            
            .webgsm-tabs { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
            .webgsm-tab { padding: 10px 16px; background: #f1f5f9; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; transition: all 0.2s; }
            .webgsm-tab:hover { background: #e2e8f0; }
            .webgsm-tab.active { background: #2563eb; color: white; }
            
            .spinner { animation: spin 1s linear infinite; display: inline-block; }
            @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
            
            .webgsm-full { grid-column: 1 / -1; }
            .webgsm-checklist { list-style: none; padding: 0; margin: 0; }
            .webgsm-checklist li { padding: 8px 0; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 10px; }
            .webgsm-checklist li:last-child { border-bottom: none; }
            .check-icon { width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; }
            .check-icon.done { background: #d1fae5; color: #059669; }
            .check-icon.pending { background: #f1f5f9; color: #9ca3af; }
        </style>
        <?php
    }
    
    public function render_admin_page() {
        $cats_done = get_option('webgsm_v2_categories', false);
        $attrs_done = get_option('webgsm_v2_attributes', false);
        $menu_done = get_option('webgsm_v2_menu', false);
        $filters_done = get_option('webgsm_v2_filters', false);
        ?>
        <div class="webgsm-wrap">
            
            <div class="webgsm-header">
                <h1>🚀 WebGSM Setup Wizard v2</h1>
                <p>Structură finală: Piese • Unelte • Accesorii • Dispozitive • Servicii</p>
            </div>
            
            <!-- Preview Tabs -->
            <div class="webgsm-tabs">
                <div class="webgsm-tab active">🔧 Piese</div>
                <div class="webgsm-tab">🛠️ Unelte</div>
                <div class="webgsm-tab">📦 Accesorii</div>
                <div class="webgsm-tab">📱 Dispozitive</div>
                <div class="webgsm-tab">⚡ Servicii</div>
            </div>
            
            <div class="webgsm-grid">
                
                <!-- 1. Categorii -->
                <div class="webgsm-card">
                    <div class="webgsm-card-header">
                        <div class="webgsm-card-icon blue">📁</div>
                        <h3>1. Creare Categorii</h3>
                    </div>
                    <p>Creează 5 categorii principale + ~50 subcategorii pentru piese, unelte, accesorii, dispozitive și servicii.</p>
                    <div class="webgsm-preview">Piese/
├── Piese iPhone → Ecrane, Baterii, Camere...
├── Piese Samsung → Ecrane, Baterii, Flexuri...
├── Piese Huawei, Piese Xiaomi...
Unelte/
├── Șurubelnițe, Pensete, Stații Lipit...
Accesorii/
├── Huse & Carcase, Folii Protecție...
Dispozitive/
├── Telefoane Folosite, Tablete...
Servicii/
├── Reparații, Training, Buy-back...</div>
                    <button class="webgsm-btn webgsm-btn-primary" id="btn-cats" <?php echo $cats_done ? 'disabled' : ''; ?>>
                        <?php echo $cats_done ? '✅ Categorii create' : '📁 Creează Categorii'; ?>
                    </button>
                    <div class="webgsm-status" id="status-cats"></div>
                </div>
                
                <!-- 2. Atribute -->
                <div class="webgsm-card">
                    <div class="webgsm-card-header">
                        <div class="webgsm-card-icon green">🏷️</div>
                        <h3>2. Creare Atribute</h3>
                    </div>
                    <p>Creează 6 atribute pentru filtrare: Model, Calitate, Brand Piesă, Tehnologie, Brand Telefon, Culoare.</p>
                    <div class="webgsm-preview">Model: iPhone 16 Pro Max ... Galaxy S24 Ultra...
Calitate: Original, Premium OEM, Aftermarket...
Brand Piesă: JK Incell, GX OLED, Ampsentrix...
Tehnologie: Soft OLED, Hard OLED, Incell...
Brand Telefon: Apple, Samsung, Huawei...
Culoare: Negru, Alb, Auriu...</div>
                    <button class="webgsm-btn webgsm-btn-primary" id="btn-attrs" <?php echo $attrs_done ? 'disabled' : ''; ?>>
                        <?php echo $attrs_done ? '✅ Atribute create' : '🏷️ Creează Atribute'; ?>
                    </button>
                    <div class="webgsm-status" id="status-attrs"></div>
                </div>
                
                <!-- 3. Meniu -->
                <div class="webgsm-card">
                    <div class="webgsm-card-header">
                        <div class="webgsm-card-icon purple">🍔</div>
                        <h3>3. Creare Meniu</h3>
                    </div>
                    <p>Creează meniul principal cu 5 tab-uri și îl asociază la locațiile temei Martfury.</p>
                    <div class="webgsm-preview">┌─────────┬─────────┬─────────────┬───────────┬──────────┐
│  Piese  │ Unelte  │  Accesorii  │ Dispozitive│ Servicii │
└─────────┴─────────┴─────────────┴───────────┴──────────┘
Piese → 3 nivele: Piese iPhone > Ecrane, Baterii...
Unelte / Accesorii → Dropdown cu categorii
Dispozitive / Servicii → Dropdown simplu</div>
                    <button class="webgsm-btn webgsm-btn-primary" id="btn-menu" <?php echo $menu_done ? 'disabled' : ''; ?>>
                        <?php echo $menu_done ? '✅ Meniu creat' : '🍔 Creează Meniu'; ?>
                    </button>
                    <div class="webgsm-status" id="status-menu"></div>
                </div>
                
                <!-- 4. Filtre -->
                <div class="webgsm-card">
                    <div class="webgsm-card-header">
                        <div class="webgsm-card-icon orange">🔍</div>
                        <h3>4. Configurare Filtre</h3>
                    </div>
                    <p>Adaugă widget-uri de filtrare în sidebar: Model, Calitate, Brand, Tehnologie, Preț.</p>
                    <div class="webgsm-preview">SIDEBAR SHOP:
├── 🔍 Filtru Model (iPhone 14, S24...)
├── 🔍 Filtru Calitate (OEM, Aftermarket...)
├── 🔍 Filtru Brand Piesă (JK, GX...)
├── 🔍 Filtru Tehnologie (OLED, LCD...)
└── 💰 Filtru Preț (slider)</div>
                    <button class="webgsm-btn webgsm-btn-primary" id="btn-filters" <?php echo $filters_done ? 'disabled' : ''; ?>>
                        <?php echo $filters_done ? '✅ Filtre configurate' : '🔍 Configurează Filtre'; ?>
                    </button>
                    <div class="webgsm-status" id="status-filters"></div>
                </div>
                
                <!-- Status General -->
                <div class="webgsm-card webgsm-full">
                    <div class="webgsm-card-header">
                        <div class="webgsm-card-icon blue">📊</div>
                        <h3>Status Setup</h3>
                    </div>
                    <ul class="webgsm-checklist">
                        <li>
                            <span class="check-icon <?php echo $cats_done ? 'done' : 'pending'; ?>"><?php echo $cats_done ? '✓' : '○'; ?></span>
                            <span>Categorii WooCommerce (~55 categorii)</span>
                        </li>
                        <li>
                            <span class="check-icon <?php echo $attrs_done ? 'done' : 'pending'; ?>"><?php echo $attrs_done ? '✓' : '○'; ?></span>
                            <span>Atribute pentru filtrare (6 atribute, ~150 termeni)</span>
                        </li>
                        <li>
                            <span class="check-icon <?php echo $menu_done ? 'done' : 'pending'; ?>"><?php echo $menu_done ? '✓' : '○'; ?></span>
                            <span>Meniu navigare principal (5 tab-uri)</span>
                        </li>
                        <li>
                            <span class="check-icon <?php echo $filters_done ? 'done' : 'pending'; ?>"><?php echo $filters_done ? '✓' : '○'; ?></span>
                            <span>Widget-uri filtrare sidebar</span>
                        </li>
                    </ul>
                    
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb; display: flex; gap: 10px;">
                        <button class="webgsm-btn" style="background: #f59e0b; color: white;" id="btn-reset">
                            🔄 Reset Flags
                        </button>
                        <button class="webgsm-btn webgsm-btn-danger" id="btn-cleanup">
                            🗑️ Șterge Tot (Categorii + Subcategorii + Tags + Atribute + Meniu)
                        </button>
                    </div>
                </div>
                
            </div>
            
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            
            function doAjax(action, btnId, statusId) {
                var $btn = $('#' + btnId);
                var $status = $('#' + statusId);
                var originalText = $btn.html();
                
                $btn.prop('disabled', true).html('<span class="spinner">⏳</span> Se procesează...');
                $status.removeClass('success error').addClass('loading show').text('Se procesează...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: { action: action, nonce: '<?php echo wp_create_nonce('webgsm_v2'); ?>' },
                    success: function(response) {
                        if (response.success) {
                            $status.removeClass('loading').addClass('success').html('✅ ' + response.data.message);
                            $btn.html('✅ Gata!').addClass('webgsm-btn-success');
                        } else {
                            $status.removeClass('loading').addClass('error').html('❌ ' + (response.data ? response.data.message : 'Eroare'));
                            $btn.prop('disabled', false).html(originalText);
                        }
                    },
                    error: function() {
                        $status.removeClass('loading').addClass('error').text('❌ Eroare de conexiune');
                        $btn.prop('disabled', false).html(originalText);
                    }
                });
            }
            
            $('#btn-cats').on('click', function() { doAjax('webgsm_v2_create_categories', 'btn-cats', 'status-cats'); });
            $('#btn-attrs').on('click', function() { doAjax('webgsm_v2_create_attributes', 'btn-attrs', 'status-attrs'); });
            $('#btn-menu').on('click', function() { doAjax('webgsm_v2_create_menu', 'btn-menu', 'status-menu'); });
            $('#btn-filters').on('click', function() { doAjax('webgsm_v2_setup_filters', 'btn-filters', 'status-filters'); });
            
            $('#btn-reset').on('click', function() {
                if (confirm('Reset flags? Vei putea rula din nou toți pașii.')) {
                    $.post(ajaxurl, { action: 'webgsm_v2_reset', nonce: '<?php echo wp_create_nonce('webgsm_v2'); ?>' }, function() {
                        location.reload();
                    });
                }
            });
            
            $('#btn-cleanup').on('click', function() {
                if (confirm('⚠️ ATENȚIE: Se vor ȘTERGE TOATE categoriile (inclusiv subcategorii), tags, atribute și meniul! Continui?')) {
                    if (confirm('Ești absolut sigur? Această acțiune este ireversibilă!')) {
                        doAjax('webgsm_v2_cleanup', 'btn-cleanup', 'status-filters');
                    }
                }
            });
            
        });
        </script>
        <?php
    }
    
    // ===========================================
    // AJAX: Creare Categorii (suportă 2 sau 3 nivele: Piese > Piese iPhone > Ecrane)
    // ===========================================
    public function ajax_create_categories() {
        check_ajax_referer('webgsm_v2', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Nu ai permisiuni']);
        
        $created = 0;
        
        foreach ($this->categories as $parent_name => $parent_data) {
            // Nivel 1: categoria părinte
            $parent_id = $this->create_category($parent_name, $parent_data['slug'], 0, $parent_data['description'] ?? '');
            if ($parent_id) $created++;
            
            if (empty($parent_data['children'])) continue;
            
            foreach ($parent_data['children'] as $child_name => $child_value) {
                // Nivel 2: fie slug simplu (name => slug), fie array cu slug + children (3 nivele)
                if (is_array($child_value) && isset($child_value['slug'])) {
                    // 3 nivele: Piese > Piese iPhone > Ecrane
                    $level2_id = $this->create_category($child_name, $child_value['slug'], $parent_id);
                    if ($level2_id) $created++;
                    
                    if (!empty($child_value['children'])) {
                        $level2_slug = $child_value['slug'];
                        $brand_suffix = str_replace('piese-', '', $level2_slug); // iphone, samsung, etc.
                        foreach ($child_value['children'] as $sub_name => $sub_slug) {
                            $sub_slug_unique = $sub_slug . '-' . $brand_suffix; // ecrane-iphone, baterii-samsung
                            $sub_id = $this->create_category($sub_name, $sub_slug_unique, $level2_id);
                            if ($sub_id) $created++;
                        }
                    }
                } else {
                    // 2 nivele: Unelte > Șurubelnițe (child_value e string slug)
                    $child_slug = is_string($child_value) ? $child_value : sanitize_title($child_name);
                    $child_id = $this->create_category($child_name, $child_slug, $parent_id);
                    if ($child_id) $created++;
                }
            }
        }
        
        update_option('webgsm_v2_categories', true);
        wp_send_json_success(['message' => "Au fost create {$created} categorii!"]);
    }
    
    private function create_category($name, $slug, $parent = 0, $desc = '') {
        $existing = get_term_by('slug', $slug, 'product_cat');
        if ($existing) return $existing->term_id;
        
        $result = wp_insert_term($name, 'product_cat', [
            'slug' => $slug,
            'parent' => $parent,
            'description' => $desc
        ]);
        
        return is_wp_error($result) ? false : $result['term_id'];
    }
    
    /** Șterge o categorie și toți descendenții (recursiv, de la frunze la rădăcină). */
    private function delete_category_and_children($term_id) {
        $children = get_terms(['taxonomy' => 'product_cat', 'parent' => $term_id, 'hide_empty' => false]);
        foreach ($children as $child) {
            $this->delete_category_and_children($child->term_id);
        }
        wp_delete_term($term_id, 'product_cat');
    }
    
    // ===========================================
    // AJAX: Creare Atribute
    // ===========================================
    public function ajax_create_attributes() {
        check_ajax_referer('webgsm_v2', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Nu ai permisiuni']);
        
        global $wpdb;
        $created_attrs = 0;
        $created_terms = 0;
        
        foreach ($this->attributes as $attr_name => $attr_data) {
            $attr_slug = $attr_data['slug'];
            
            // Verifică dacă atributul există
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT attribute_id FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s",
                $attr_slug
            ));
            
            if (!$exists) {
                $wpdb->insert($wpdb->prefix . 'woocommerce_attribute_taxonomies', [
                    'attribute_name' => $attr_slug,
                    'attribute_label' => $attr_name,
                    'attribute_type' => 'select',
                    'attribute_orderby' => 'menu_order',
                    'attribute_public' => 0
                ]);
                $created_attrs++;
                delete_transient('wc_attribute_taxonomies');
            }
            
            // Înregistrează taxonomia
            $taxonomy = 'pa_' . $attr_slug;
            if (!taxonomy_exists($taxonomy)) {
                register_taxonomy($taxonomy, 'product', [
                    'label' => $attr_name,
                    'public' => false,
                    'show_ui' => true,
                    'hierarchical' => false,
                ]);
            }
            
            // Creează termenii
            foreach ($attr_data['terms'] as $term_name) {
                $term_slug = sanitize_title($term_name);
                if (!term_exists($term_slug, $taxonomy)) {
                    $result = wp_insert_term($term_name, $taxonomy, ['slug' => $term_slug]);
                    if (!is_wp_error($result)) $created_terms++;
                }
            }
        }
        
        flush_rewrite_rules();
        update_option('webgsm_v2_attributes', true);
        wp_send_json_success(['message' => "Create {$created_attrs} atribute noi și {$created_terms} termeni!"]);
    }
    
    // ===========================================
    // AJAX: Creare Meniu
    // ===========================================
    public function ajax_create_menu() {
        check_ajax_referer('webgsm_v2', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Nu ai permisiuni']);
        
        $menu_name = 'WebGSM Main Menu';
        
        // Șterge meniul existent
        $existing = wp_get_nav_menu_object($menu_name);
        if ($existing) {
            wp_delete_nav_menu($existing->term_id);
        }
        
        // Creează meniu nou
        $menu_id = wp_create_nav_menu($menu_name);
        if (is_wp_error($menu_id)) {
            wp_send_json_error(['message' => 'Eroare la crearea meniului']);
        }
        
        $items_count = 0;
        $order = 1;
        
        foreach ($this->categories as $parent_name => $parent_data) {
            $parent_term = get_term_by('slug', $parent_data['slug'], 'product_cat');
            if (!$parent_term) continue;
            
            $is_mega = in_array($parent_name, ['Piese', 'Unelte', 'Accesorii']);
            
            $parent_menu_id = wp_update_nav_menu_item($menu_id, 0, [
                'menu-item-title' => $parent_name,
                'menu-item-object' => 'product_cat',
                'menu-item-object-id' => $parent_term->term_id,
                'menu-item-type' => 'taxonomy',
                'menu-item-status' => 'publish',
                'menu-item-classes' => $is_mega ? 'mf-mega-menu' : '',
                'menu-item-position' => $order++
            ]);
            $items_count++;
            
            if (empty($parent_data['children'])) continue;
            
            foreach ($parent_data['children'] as $child_name => $child_value) {
                if (is_array($child_value) && isset($child_value['slug'])) {
                    $child_term = get_term_by('slug', $child_value['slug'], 'product_cat');
                    if (!$child_term) continue;
                    $level2_menu_id = wp_update_nav_menu_item($menu_id, 0, [
                        'menu-item-title' => $child_name,
                        'menu-item-object' => 'product_cat',
                        'menu-item-object-id' => $child_term->term_id,
                        'menu-item-type' => 'taxonomy',
                        'menu-item-status' => 'publish',
                        'menu-item-parent-id' => $parent_menu_id,
                        'menu-item-position' => $order++
                    ]);
                    $items_count++;
                    if (!empty($child_value['children'])) {
                        $level2_slug = $child_value['slug'];
                        $brand_suffix = str_replace('piese-', '', $level2_slug);
                        foreach ($child_value['children'] as $sub_name => $sub_slug) {
                            $sub_slug_unique = $sub_slug . '-' . $brand_suffix;
                            $sub_term = get_term_by('slug', $sub_slug_unique, 'product_cat');
                            if (!$sub_term) continue;
                            wp_update_nav_menu_item($menu_id, 0, [
                                'menu-item-title' => $sub_name,
                                'menu-item-object' => 'product_cat',
                                'menu-item-object-id' => $sub_term->term_id,
                                'menu-item-type' => 'taxonomy',
                                'menu-item-status' => 'publish',
                                'menu-item-parent-id' => $level2_menu_id,
                                'menu-item-position' => $order++
                            ]);
                            $items_count++;
                        }
                    }
                } else {
                    $child_slug = is_string($child_value) ? $child_value : sanitize_title($child_name);
                    $child_term = get_term_by('slug', $child_slug, 'product_cat');
                    if (!$child_term) continue;
                    wp_update_nav_menu_item($menu_id, 0, [
                        'menu-item-title' => $child_name,
                        'menu-item-object' => 'product_cat',
                        'menu-item-object-id' => $child_term->term_id,
                        'menu-item-type' => 'taxonomy',
                        'menu-item-status' => 'publish',
                        'menu-item-parent-id' => $parent_menu_id,
                        'menu-item-position' => $order++
                    ]);
                    $items_count++;
                }
            }
        }
        
        // Asociază la locații
        $locations = get_theme_mod('nav_menu_locations', []);
        $locations['primary'] = $menu_id;
        $locations['primary-menu'] = $menu_id;
        $locations['shop-department'] = $menu_id;
        set_theme_mod('nav_menu_locations', $locations);
        
        update_option('webgsm_v2_menu', true);
        wp_send_json_success(['message' => "Meniu creat cu {$items_count} itemi!"]);
    }
    
    // ===========================================
    // AJAX: Setup Filtre
    // ===========================================
    public function ajax_setup_filters() {
        check_ajax_referer('webgsm_v2', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Nu ai permisiuni']);
        
        $sidebars = get_option('sidebars_widgets', []);
        
        // Găsește sidebar-ul shop
        $shop_sidebar = null;
        foreach (['catalog-sidebar', 'shop-sidebar', 'sidebar-shop', 'woocommerce-sidebar'] as $s) {
            if (isset($sidebars[$s])) { $shop_sidebar = $s; break; }
        }
        
        if (!$shop_sidebar) {
            $shop_sidebar = 'catalog-sidebar';
            $sidebars[$shop_sidebar] = [];
        }
        
        // Curăță și adaugă widget-uri noi
        $sidebars[$shop_sidebar] = [];
        
        $attrs_to_filter = ['model', 'calitate', 'brand-piesa', 'tehnologie'];
        $widget_id = 1;
        
        foreach ($attrs_to_filter as $attr_slug) {
            $attr_labels = [
                'model' => 'Model',
                'calitate' => 'Calitate', 
                'brand-piesa' => 'Brand Piesă',
                'tehnologie' => 'Tehnologie'
            ];
            
            $widget_data = get_option('widget_woocommerce_layered_nav', []);
            $widget_data[$widget_id] = [
                'title' => $attr_labels[$attr_slug] ?? ucfirst($attr_slug),
                'attribute' => $attr_slug,
                'display_type' => 'list',
                'query_type' => 'or'
            ];
            update_option('widget_woocommerce_layered_nav', $widget_data);
            
            $sidebars[$shop_sidebar][] = 'woocommerce_layered_nav-' . $widget_id;
            $widget_id++;
        }
        
        // Adaugă filtru preț
        $price_widget = get_option('widget_woocommerce_price_filter', []);
        $price_widget[1] = ['title' => 'Preț'];
        update_option('widget_woocommerce_price_filter', $price_widget);
        $sidebars[$shop_sidebar][] = 'woocommerce_price_filter-1';
        
        update_option('sidebars_widgets', $sidebars);
        update_option('webgsm_v2_filters', true);
        
        wp_send_json_success(['message' => "Filtre configurate în sidebar!"]);
    }
    
    // ===========================================
    // AJAX: Reset Flags
    // ===========================================
    public function ajax_reset() {
        check_ajax_referer('webgsm_v2', 'nonce');
        delete_option('webgsm_v2_categories');
        delete_option('webgsm_v2_attributes');
        delete_option('webgsm_v2_menu');
        delete_option('webgsm_v2_filters');
        wp_send_json_success();
    }
    
    // ===========================================
    // AJAX: Cleanup (șterge tot: categorii + subcategorii + tags + atribute + meniu)
    // ===========================================
    public function ajax_cleanup() {
        check_ajax_referer('webgsm_v2', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Nu ai permisiuni']);
        
        global $wpdb;
        
        // 1) Șterge TOATE categoriile de produse (inclusiv subcategoriile rămase) – de la frunze la rădăcină
        $deleted_cats = 0;
        do {
            $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false, 'fields' => 'all']);
            if (empty($terms)) break;
            $ids = wp_list_pluck($terms, 'term_id');
            $to_delete = [];
            foreach ($terms as $t) {
                $has_child_in_list = false;
                foreach ($terms as $t2) {
                    if ((int) $t2->parent === (int) $t->term_id) {
                        $has_child_in_list = true;
                        break;
                    }
                }
                if (!$has_child_in_list) $to_delete[] = $t->term_id;
            }
            foreach ($to_delete as $tid) {
                if (!is_wp_error(wp_delete_term($tid, 'product_cat'))) $deleted_cats++;
            }
        } while (!empty($terms));
        
        // 2) Șterge TOATE tag-urile de produse
        $tag_terms = get_terms(['taxonomy' => 'product_tag', 'hide_empty' => false, 'fields' => 'ids']);
        foreach ($tag_terms as $tid) {
            wp_delete_term($tid, 'product_tag');
        }
        
        // 3) Șterge TOATE atributele WooCommerce (gol total)
        $attribute_taxonomies = wc_get_attribute_taxonomies();
        foreach ($attribute_taxonomies as $attr) {
            $wpdb->delete($wpdb->prefix . 'woocommerce_attribute_taxonomies', ['attribute_id' => $attr->attribute_id]);
        }
        delete_transient('wc_attribute_taxonomies');
        
        // 4) Șterge meniul WebGSM (să fie meniul gol)
        $menu = wp_get_nav_menu_object('WebGSM Main Menu');
        if ($menu) wp_delete_nav_menu($menu->term_id);
        
        // 5) Reset flags wizard
        delete_option('webgsm_v2_categories');
        delete_option('webgsm_v2_attributes');
        delete_option('webgsm_v2_menu');
        delete_option('webgsm_v2_filters');
        
        wp_send_json_success(['message' => 'Tot șters: categorii (inclusiv subcategorii), tags, atribute, meniu. Poți începe din nou.']);
    }
}

WebGSM_Setup_Wizard_V2::instance();
