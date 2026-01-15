<?php
/**
 * MODUL GARANȚIE
 * Gestionează cererile de garanție și perioadele de garanție per produs
 * - Verifică garanția de la DATA LIVRĂRII
 * - Permite selectare cantitate
 * - Upload poze
 */

// Adauga tab-ul "Garantie" in My Account
add_filter('woocommerce_account_menu_items', function($items) {
    $new_items = array();
    foreach($items as $key => $value) {
        $new_items[$key] = $value;
        if($key === 'retururi') {
            $new_items['garantie'] = 'Garanție';
        }
    }
    return $new_items;
}, 20);

// Creează endpoint-ul pentru garanție
add_action('init', function() {
    add_rewrite_endpoint('garantie', EP_ROOT | EP_PAGES);
});

// Înregistrează Custom Post Type pentru Cereri Garanție
add_action('init', function() {
    register_post_type('cerere_garantie', array(
        'labels' => array(
            'name' => 'Cereri Garantie',
            'singular_name' => 'Cerere Garantie',
            'menu_name' => 'Cereri Garantie',
            'all_items' => 'Toate cererile',
            'view_item' => 'Vezi cererea',
            'edit_item' => 'Editează cererea'
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 57,
        'menu_icon' => 'dashicons-shield',
        'supports' => array('title'),
        'capability_type' => 'post'
    ));
});

// Ia perioada de garanție a unui produs (în luni)
function get_garantie_produs($product_id) {
    $garantie = get_post_meta($product_id, '_perioada_garantie', true);
    return $garantie ? intval($garantie) : 12; // Default 12 luni
}

// Funcție helper: ia data livrării (când comanda a devenit "completed")
if(!function_exists('get_delivery_date')) {
    function get_delivery_date($order) {
        $date_completed = $order->get_date_completed();
        if($date_completed) {
            return $date_completed->getTimestamp();
        }
        return $order->get_date_created()->getTimestamp();
    }
}

// Verifică dacă produsul e încă în garanție (de la data livrării)
function produs_in_garantie($delivery_timestamp, $product_id) {
    $luni_garantie = get_garantie_produs($product_id);
    if($luni_garantie == 0) return false;
    $data_expirare = strtotime('+' . $luni_garantie . ' months', $delivery_timestamp);
    return time() < $data_expirare;
}

// Calculează cantitatea deja trimisă în garanție (cu cereri active)
function get_qty_in_garantie_activa($order_id, $product_id, $customer_id) {
    $garantii = get_posts(array(
        'post_type' => 'cerere_garantie',
        'author' => $customer_id,
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => '_order_id',
                'value' => $order_id
            ),
            array(
                'key' => '_product_id',
                'value' => $product_id
            ),
            array(
                'key' => '_status_garantie',
                'value' => array('finalizat', 'respins'),
                'compare' => 'NOT IN'
            )
        ),
        'numberposts' => -1
    ));
    
    $total = 0;
    foreach($garantii as $garantie) {
        $qty = get_post_meta($garantie->ID, '_qty_garantie', true);
        $total += $qty ? intval($qty) : 1;
    }
    
    return $total;
}

// Funcție helper: upload poze garanție
function upload_poze_garantie($files, $garantie_id) {
    if(!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }
    if(!function_exists('wp_generate_attachment_metadata')) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
    }
    
    $uploaded_ids = array();
    $allowed_types = array('image/jpeg', 'image/png', 'image/jpg');
    
    $file_count = count($files['name']);
    
    for($i = 0; $i < $file_count; $i++) {
        if($files['error'][$i] !== UPLOAD_ERR_OK || empty($files['name'][$i])) {
            continue;
        }
        
        if(!in_array($files['type'][$i], $allowed_types)) {
            continue;
        }
        
        if($files['size'][$i] > 5 * 1024 * 1024) {
            continue;
        }
        
        $file = array(
            'name' => $files['name'][$i],
            'type' => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i]
        );
        
        $upload = wp_handle_upload($file, array('test_form' => false));
        
        if($upload && !isset($upload['error'])) {
            $attachment = array(
                'post_mime_type' => $upload['type'],
                'post_title' => sanitize_file_name($file['name']),
                'post_content' => '',
                'post_status' => 'inherit'
            );
            
            $attach_id = wp_insert_attachment($attachment, $upload['file'], $garantie_id);
            
            if($attach_id) {
                $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
                wp_update_attachment_metadata($attach_id, $attach_data);
                $uploaded_ids[] = $attach_id;
            }
        }
    }
    
    return $uploaded_ids;
}

// Conținutul paginii de garanție
add_action('woocommerce_account_garantie_endpoint', function() {
    $customer_id = get_current_user_id();
    
    // Mesaj de succes după redirect
    if(isset($_GET['garantie_success'])) {
        echo '<div class="woocommerce-message">Cererea de garantie a fost trimisa cu succes! Vei fi contactat in curand.</div>';
    }
    
    // Procesare formular garanție
    if(isset($_POST['submit_garantie']) && wp_verify_nonce($_POST['garantie_nonce'], 'submit_garantie_form')) {
        $order_id = intval($_POST['order_id']);
        $product_id = intval($_POST['product_id']);
        $qty_garantie = intval($_POST['qty_garantie']);
        $descriere = sanitize_textarea_field($_POST['descriere_problema']);
        
        // Validare câmpuri obligatorii
        if(empty($order_id) || empty($product_id) || empty($descriere) || $qty_garantie < 1) {
            echo '<div class="woocommerce-error">Te rugam sa completezi toate campurile obligatorii.</div>';
        }
        else {
            $order = wc_get_order($order_id);
            $delivery_timestamp = get_delivery_date($order);
            
            // Verifică cantitatea disponibilă
            $qty_available = 0;
            foreach($order->get_items() as $item) {
                if($item->get_product_id() == $product_id) {
                    $qty_ordered = $item->get_quantity();
                    $qty_in_garantie = get_qty_in_garantie_activa($order_id, $product_id, $customer_id);
                    $qty_available = $qty_ordered - $qty_in_garantie;
                    break;
                }
            }
            
            if($qty_garantie > $qty_available) {
                echo '<div class="woocommerce-error">Nu poti trimite in garantie mai multe bucati decat ai disponibile (' . $qty_available . ').</div>';
            }
            elseif(!produs_in_garantie($delivery_timestamp, $product_id)) {
                $luni = get_garantie_produs($product_id);
                echo '<div class="woocommerce-error">Perioada de garanție de ' . $luni . ' luni a expirat pentru acest produs.</div>';
            }
            else {
                // Salvează cererea de garanție
                $garantie_id = wp_insert_post(array(
                    'post_type' => 'cerere_garantie',
                    'post_title' => 'Garantie #' . $order_id . ' - ' . date('Y-m-d H:i'),
                    'post_status' => 'publish',
                    'post_author' => $customer_id
                ));
                
                if($garantie_id) {
                    update_post_meta($garantie_id, '_order_id', $order_id);
                    update_post_meta($garantie_id, '_product_id', $product_id);
                    update_post_meta($garantie_id, '_qty_garantie', $qty_garantie);
                    update_post_meta($garantie_id, '_descriere_problema', $descriere);
                    update_post_meta($garantie_id, '_status_garantie', 'nou');
                    update_post_meta($garantie_id, '_customer_id', $customer_id);
                    
                    // Upload poze dacă există
                    if(!empty($_FILES['poze_garantie']['name'][0])) {
                        $uploaded_ids = upload_poze_garantie($_FILES['poze_garantie'], $garantie_id);
                        if(!empty($uploaded_ids)) {
                            update_post_meta($garantie_id, '_poze_garantie', $uploaded_ids);
                        }
                    }
                    
                    // Trigger pentru n8n webhook
                    do_action('cerere_garantie_noua', $garantie_id, $order_id, $product_id, $customer_id);
                    
                    // Redirect cu JavaScript
                    echo '<script>window.location.href = "' . wc_get_account_endpoint_url('garantie') . '?garantie_success=1";</script>';
                    exit;
                }
            }
        }
    }
    
    // Afișează garanțiile existente ale clientului
    $garantii = get_posts(array(
        'post_type' => 'cerere_garantie',
        'author' => $customer_id,
        'numberposts' => -1,
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    
    if(!empty($garantii)) {
        echo '<h3>Cererile tale de garantie</h3>';
        echo '<table class="woocommerce-orders-table" style="width:100%; margin-bottom:30px;">';
        echo '<thead><tr><th>Data</th><th>Comandă</th><th>Produs</th><th>Cantitate</th><th>Status</th></tr></thead>';
        echo '<tbody>';
        foreach($garantii as $garantie) {
            $status = get_post_meta($garantie->ID, '_status_garantie', true);
            $order_id = get_post_meta($garantie->ID, '_order_id', true);
            $product_id = get_post_meta($garantie->ID, '_product_id', true);
            $qty = get_post_meta($garantie->ID, '_qty_garantie', true);
            $product = wc_get_product($product_id);
            
            $status_label = array(
                'nou' => '<span style="color:orange;">Nou</span>',
                'in_analiza' => '<span style="color:purple;">În analiză</span>',
                'aprobat' => '<span style="color:green;">Aprobat</span>',
                'respins' => '<span style="color:red;">Respins</span>',
                'in_reparatie' => '<span style="color:blue;">În reparație</span>',
                'finalizat' => '<span style="color:gray;">Finalizat</span>'
            );
            
            echo '<tr>';
            echo '<td>' . get_the_date('d.m.Y', $garantie) . '</td>';
            echo '<td>#' . $order_id . '</td>';
            echo '<td>' . ($product ? $product->get_name() : '-') . '</td>';
            echo '<td>' . ($qty ? $qty : 1) . ' buc</td>';
            echo '<td>' . ($status_label[$status] ?? $status) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
    
    // Formularul de garantie nou
    echo '<h3>Solicita service in garantie</h3>';
    echo '<p><em>Perioada de garantie variaza in functie de tipul produsului si se calculeaza de la data livrarii.</em></p>';
    
    // Ia toate comenzile completate ale clientului
    $orders = wc_get_orders(array(
        'customer_id' => $customer_id,
        'status' => array('completed'),
        'limit' => 100
    ));
    
    if(empty($orders)) {
        echo '<p>Nu ai comenzi eligibile pentru garantie.</p>';
        return;
    }
    
    ?>
    <form method="post" class="garantie-form" enctype="multipart/form-data">
        <?php wp_nonce_field('submit_garantie_form', 'garantie_nonce'); ?>
        
        <p class="form-row">
            <label>Selecteaza comanda *</label>
            <select name="order_id" id="select_order_garantie" required style="width:100%;">
                <option value="">-- Alege comanda --</option>
                <?php foreach($orders as $order): 
                    $delivery_date = get_delivery_date($order);
                ?>
                    <option value="<?php echo $order->get_id(); ?>">
                        #<?php echo $order->get_order_number(); ?> - Livrat: <?php echo date('d.m.Y', $delivery_date); ?> - <?php echo $order->get_total(); ?> lei
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        
        <p class="form-row">
            <label>Selecteaza produsul *</label>
            <select name="product_id" id="select_product_garantie" required style="width:100%;">
                <option value="">-- Alege mai intai comanda --</option>
            </select>
        </p>
        
        <p class="form-row" id="info_garantie" style="display:none; background:#f0f0f0; padding:10px; border-radius:5px;">
        </p>
        
        <p class="form-row" id="qty_garantie_row" style="display:none;">
            <label>Cantitate pentru garantie *</label>
            <select name="qty_garantie" id="select_qty_garantie" required style="width:100%;">
                <option value="1">1 bucată</option>
            </select>
        </p>
        
        <p class="form-row">
            <label>Descrie problema *</label>
            <textarea name="descriere_problema" required style="width:100%; min-height:100px;" placeholder="Te rugăm să descrii problema cât mai detaliat (ce simptome are, când a apărut problema, etc.)..."></textarea>
        </p>
        
        <p class="form-row">
            <label>Adauga poze (optional, max 5 poze, JPG/PNG, max 5MB fiecare)</label>
            <div class="file-upload-wrapper">
                <input type="file" name="poze_garantie[]" id="poze_garantie_input" multiple accept="image/jpeg,image/png,image/jpg" style="display:none;">
                <label for="poze_garantie_input" class="file-upload-button">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    Alege fisiere
                </label>
                <span class="file-upload-text">Niciun fisier selectat</span>
            </div>
            <small style="color:#666; display:block; margin-top:6px;">Pozele cu defectul ajută la procesarea mai rapidă a cererii tale.</small>
        </p>
        
        <p class="form-row">
            <button type="submit" name="submit_garantie" class="button">Trimite cererea de garantie</button>
        </p>
    </form>
    
    <script>
    jQuery(document).ready(function($) {
        // Gestionare afișare nume fișiere pentru garanție
        $('#poze_garantie_input').on('change', function() {
            var files = this.files;
            var $text = $(this).closest('.file-upload-wrapper').find('.file-upload-text');
            
            if(files.length > 5) {
                alert('Poti incarca maxim 5 poze.');
                this.value = '';
                $text.text('Niciun fisier selectat');
                return;
            }
            
            if(files.length === 0) {
                $text.text('Niciun fisier selectat');
            } else if(files.length === 1) {
                $text.text('1 fisier selectat: ' + files[0].name);
            } else {
                $text.text(files.length + ' fisiere selectate');
            }
        });
        
        $('#select_order_garantie').on('change', function() {
            var orderId = $(this).val();
            var productSelect = $('#select_product_garantie');
            $('#info_garantie').hide();
            $('#qty_garantie_row').hide();
            
            if(!orderId) {
                productSelect.html('<option value="">-- Alege mai intai comanda --</option>');
                return;
            }
            
            productSelect.html('<option value="">Se incarca...</option>');
            
            $.ajax({
                url: '<?php echo admin_url("admin-ajax.php"); ?>',
                type: 'POST',
                data: {
                    action: 'get_order_products_garantie',
                    order_id: orderId
                },
                success: function(response) {
                    if(response.success) {
                        var options = '<option value="">-- Alege produsul --</option>';
                        if(response.data.length === 0) {
                            options = '<option value="">-- Nu sunt produse eligibile pentru garantie --</option>';
                        } else {
                            $.each(response.data, function(i, product) {
                                options += '<option value="' + product.id + '" data-garantie="' + product.garantie_luni + '" data-expira="' + product.data_expirare + '" data-qty="' + product.qty_available + '">' + product.name + ' - Garantie: ' + product.garantie_luni + ' luni (disponibil: ' + product.qty_available + ' buc)</option>';
                            });
                        }
                        productSelect.html(options);
                    }
                }
            });
        });
        
        $('#select_product_garantie').on('change', function() {
            var selected = $(this).find(':selected');
            var garantie = selected.data('garantie');
            var expira = selected.data('expira');
            var qtyAvailable = selected.data('qty');
            var qtySelect = $('#select_qty_garantie');
            
            if(garantie) {
                $('#info_garantie').html('<strong>Garantie:</strong> ' + garantie + ' luni | <strong>Expira:</strong> ' + expira).show();
            } else {
                $('#info_garantie').hide();
            }
            
            if(qtyAvailable && qtyAvailable > 0) {
                var options = '';
                for(var i = 1; i <= qtyAvailable; i++) {
                    options += '<option value="' + i + '">' + i + ' bucata' + (i > 1 ? 'ti' : '') + '</option>';
                }
                qtySelect.html(options);
                $('#qty_garantie_row').show();
            } else {
                $('#qty_garantie_row').hide();
            }
        });
    });
    </script>
    <?php
});

// AJAX pentru produse garanție
add_action('wp_ajax_get_order_products_garantie', function() {
    $order_id = intval($_POST['order_id']);
    $order = wc_get_order($order_id);
    $customer_id = get_current_user_id();
    
    if(!$order || $order->get_customer_id() !== $customer_id) {
        wp_send_json_error();
    }
    
    $delivery_timestamp = get_delivery_date($order);
    $products = array();
    
    foreach($order->get_items() as $item) {
        $product_id = $item->get_product_id();
        $qty_ordered = $item->get_quantity();
        
        // Verifică dacă produsul e în garanție
        if(produs_in_garantie($delivery_timestamp, $product_id)) {
            $qty_in_garantie = get_qty_in_garantie_activa($order_id, $product_id, $customer_id);
            $qty_available = $qty_ordered - $qty_in_garantie;
            
            if($qty_available > 0) {
                $luni_garantie = get_garantie_produs($product_id);
                $data_expirare = date('d.m.Y', strtotime('+' . $luni_garantie . ' months', $delivery_timestamp));
                
                $products[] = array(
                    'id' => $product_id,
                    'name' => $item->get_name(),
                    'qty_ordered' => $qty_ordered,
                    'qty_available' => $qty_available,
                    'garantie_luni' => $luni_garantie,
                    'data_expirare' => $data_expirare
                );
            }
        }
    }
    
    wp_send_json_success($products);
});

// =============================================
// CÂMP GARANȚIE ÎN ADMIN PRODUS
// =============================================

add_filter('woocommerce_product_data_tabs', function($tabs) {
    $tabs['garantie'] = array(
        'label' => 'Garantie',
        'target' => 'garantie_product_data',
        'priority' => 25
    );
    return $tabs;
});

add_action('woocommerce_product_data_panels', function() {
    echo '<div id="garantie_product_data" class="panel woocommerce_options_panel">';
    
    woocommerce_wp_select(array(
        'id' => '_perioada_garantie',
        'label' => 'Perioada garantie',
        'description' => 'Selecteaza perioada de garantie pentru acest produs',
        'desc_tip' => true,
        'options' => array(
            '0' => 'Fara garantie',
            '1' => '1 lună',
            '3' => '3 luni',
            '6' => '6 luni',
            '12' => '12 luni (1 an)',
            '24' => '24 luni (2 ani)',
            '36' => '36 luni (3 ani)'
        )
    ));
    
    echo '<p class="form-field"><strong>Sugestii pentru piese GSM:</strong><br>';
    echo '• LCD/Display: 12 luni<br>';
    echo '• Acumulatori/Baterii: 6 luni<br>';
    echo '• Cabluri flex: 6 luni<br>';
    echo '• Carcase: 3 luni<br>';
    echo '• Accesorii: 1-3 luni</p>';
    
    echo '</div>';
});

add_action('woocommerce_process_product_meta', function($post_id) {
    if(isset($_POST['_perioada_garantie'])) {
        update_post_meta($post_id, '_perioada_garantie', sanitize_text_field($_POST['_perioada_garantie']));
    }
});

// =============================================
// COLOANE ADMIN - CERERI GARANȚIE
// =============================================

add_filter('manage_cerere_garantie_posts_columns', function($columns) {
    return array(
        'cb' => $columns['cb'],
        'title' => 'Cerere',
        'order_id' => 'Comandă',
        'product' => 'Produs',
        'qty_garantie' => 'Cantitate',
        'poze' => 'Poze',
        'status_garantie' => 'Status',
        'date' => 'Data'
    );
});

add_action('manage_cerere_garantie_posts_custom_column', function($column, $post_id) {
    switch($column) {
        case 'order_id':
            $order_id = get_post_meta($post_id, '_order_id', true);
            echo '<a href="' . admin_url('post.php?post=' . $order_id . '&action=edit') . '">#' . $order_id . '</a>';
            break;
        case 'product':
            $product_id = get_post_meta($post_id, '_product_id', true);
            $product = wc_get_product($product_id);
            echo $product ? $product->get_name() : '-';
            break;
        case 'qty_garantie':
            $qty = get_post_meta($post_id, '_qty_garantie', true);
            echo ($qty ? $qty : 1) . ' buc';
            break;
        case 'poze':
            $poze = get_post_meta($post_id, '_poze_garantie', true);
            echo $poze ? count($poze) . ' poze' : '-';
            break;
        case 'status_garantie':
            $status = get_post_meta($post_id, '_status_garantie', true);
            $colors = array(
                'nou' => 'orange', 
                'in_analiza' => 'purple',
                'aprobat' => 'green', 
                'respins' => 'red', 
                'in_reparatie' => 'blue',
                'finalizat' => 'gray'
            );
            $labels = array(
                'nou' => 'Nou',
                'in_analiza' => 'În analiză',
                'aprobat' => 'Aprobat',
                'respins' => 'Respins',
                'in_reparatie' => 'În reparație',
                'finalizat' => 'Finalizat'
            );
            echo '<span style="color:' . ($colors[$status] ?? 'black') . '; font-weight:bold;">' . ($labels[$status] ?? $status) . '</span>';
            break;
    }
}, 10, 2);

// Meta box pentru editare garanție în admin
add_action('add_meta_boxes', function() {
    add_meta_box('garantie_details', 'Detalii Garantie', 'render_garantie_metabox', 'cerere_garantie', 'normal', 'high');
});

function render_garantie_metabox($post) {
    $order_id = get_post_meta($post->ID, '_order_id', true);
    $product_id = get_post_meta($post->ID, '_product_id', true);
    $qty = get_post_meta($post->ID, '_qty_garantie', true);
    $descriere = get_post_meta($post->ID, '_descriere_problema', true);
    $status = get_post_meta($post->ID, '_status_garantie', true);
    $customer_id = get_post_meta($post->ID, '_customer_id', true);
    $poze = get_post_meta($post->ID, '_poze_garantie', true);
    
    $customer = get_user_by('id', $customer_id);
    $product = wc_get_product($product_id);
    $order = wc_get_order($order_id);
    
    $garantie_luni = get_garantie_produs($product_id);
    $delivery_timestamp = $order ? get_delivery_date($order) : 0;
    $data_expirare = $delivery_timestamp ? date('d.m.Y', strtotime('+' . $garantie_luni . ' months', $delivery_timestamp)) : '-';
    ?>
    <table class="form-table">
        <tr>
            <th>Client:</th>
            <td><?php echo $customer ? $customer->display_name . ' (' . $customer->user_email . ')' : '-'; ?></td>
        </tr>
        <tr>
            <th>Comandă:</th>
            <td><a href="<?php echo admin_url('post.php?post=' . $order_id . '&action=edit'); ?>">#<?php echo $order_id; ?></a></td>
        </tr>
        <tr>
            <th>Produs:</th>
            <td><?php echo $product ? $product->get_name() : '-'; ?></td>
        </tr>
        <tr>
            <th>Cantitate:</th>
            <td><strong><?php echo $qty ? $qty : 1; ?> bucăți</strong></td>
        </tr>
        <tr>
            <th>Garantie:</th>
            <td><strong><?php echo $garantie_luni; ?> luni</strong> (expiră: <?php echo $data_expirare; ?>)</td>
        </tr>
        <tr>
            <th>Descriere problemă:</th>
            <td style="background:#f9f9f9; padding:10px;"><?php echo nl2br(esc_html($descriere)); ?></td>
        </tr>
        <?php if($poze && is_array($poze)): ?>
        <tr>
            <th>Poze atașate:</th>
            <td>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <?php foreach($poze as $poza_id): 
                    $url = wp_get_attachment_url($poza_id);
                    if($url):
                ?>
                    <a href="<?php echo $url; ?>" target="_blank">
                        <img src="<?php echo $url; ?>" style="max-width:150px; max-height:150px; border:1px solid #ddd; border-radius:4px;">
                    </a>
                <?php endif; endforeach; ?>
                </div>
            </td>
        </tr>
        <?php endif; ?>
        <tr>
            <th>Status:</th>
            <td>
                <select name="status_garantie" style="width:200px;">
                    <option value="nou" <?php selected($status, 'nou'); ?>>Nou</option>
                    <option value="in_analiza" <?php selected($status, 'in_analiza'); ?>>În analiză</option>
                    <option value="aprobat" <?php selected($status, 'aprobat'); ?>>Aprobat</option>
                    <option value="respins" <?php selected($status, 'respins'); ?>>Respins</option>
                    <option value="in_reparatie" <?php selected($status, 'in_reparatie'); ?>>În reparație</option>
                    <option value="finalizat" <?php selected($status, 'finalizat'); ?>>Finalizat</option>
                </select>
            </td>
        </tr>
    </table>
    <?php
}

// Salvează statusul la update
add_action('save_post_cerere_garantie', function($post_id) {
    if(isset($_POST['status_garantie'])) {
        $old_status = get_post_meta($post_id, '_status_garantie', true);
        $new_status = sanitize_text_field($_POST['status_garantie']);
        update_post_meta($post_id, '_status_garantie', $new_status);
        
        if($old_status !== $new_status) {
            do_action('status_garantie_schimbat', $post_id, $old_status, $new_status);
        }
    }
});

// Flush rewrite rules
add_action('after_switch_theme', function() {
    flush_rewrite_rules();
});
