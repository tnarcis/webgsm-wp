<?php
/**
 * MODUL ADMIN TOOLS
 * Permite angajaților să creeze retururi/garanții în numele clienților
 * Cu căutare după telefon, email sau nr. comandă
 */

// =============================================
// PAGINĂ ADMIN - INSTRUMENTE SUPORT
// =============================================

add_action('admin_menu', function() {
    add_menu_page(
        'Instrumente Suport',
        'Suport Clienți',
        'edit_shop_orders',
        'suport-clienti',
        'render_suport_clienti_page',
        'dashicons-headphones',
        56
    );
    
    add_submenu_page(
        'suport-clienti',
        'Creare Retur',
        'Creare Retur',
        'edit_shop_orders',
        'creare-retur',
        'render_creare_retur_page'
    );
    
    add_submenu_page(
        'suport-clienti',
        'Creare Garanție',
        'Creare Garanție',
        'edit_shop_orders',
        'creare-garantie',
        'render_creare_garantie_page'
    );
    
    add_submenu_page(
        'suport-clienti',
        'Căutare Client',
        'Căutare Client',
        'edit_shop_orders',
        'cautare-client',
        'render_cautare_client_page'
    );
    
    add_submenu_page(
        'suport-clienti',
        'Transformare PF → PJ',
        'Transformare PF → PJ',
        'manage_options',
        'transformare-pf-pj',
        'render_transformare_pf_pj_page'
    );
});

// =============================================
// PAGINĂ PRINCIPALĂ SUPORT
// =============================================

function render_suport_clienti_page() {
    ?>
    <div class="wrap">
        <h1>🎧 Instrumente Suport Clienți</h1>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 30px;">
            
            <div style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                <h3 style="margin-top: 0;">↩️ Creare Retur</h3>
                <p style="color: #666;">Creează o cerere de retur în numele unui client care a sunat.</p>
                <a href="<?php echo admin_url('admin.php?page=creare-retur'); ?>" class="button button-primary">Creează Retur</a>
            </div>
            
            <div style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                <h3 style="margin-top: 0;">🛡️ Creare Garanție</h3>
                <p style="color: #666;">Creează o cerere de garanție în numele unui client.</p>
                <a href="<?php echo admin_url('admin.php?page=creare-garantie'); ?>" class="button button-primary">Creează Garanție</a>
            </div>
            
            <div style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                <h3 style="margin-top: 0;">🔍 Căutare Client</h3>
                <p style="color: #666;">Caută un client după email, telefon sau nume pentru a vedea istoricul.</p>
                <a href="<?php echo admin_url('admin.php?page=cautare-client'); ?>" class="button button-primary">Caută Client</a>
            </div>
            
            <div style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                <h3 style="margin-top: 0;">📦 Comenzi Recente</h3>
                <p style="color: #666;">Vezi și gestionează comenzile recente.</p>
                <a href="<?php echo admin_url('edit.php?post_type=shop_order'); ?>" class="button">Vezi Comenzi</a>
            </div>
            
        </div>
    </div>
    <?php
}

// =============================================
// PAGINĂ CREARE RETUR
// =============================================

function render_creare_retur_page() {
    $message = '';
    $message_type = '';
    
    // Procesare formular
    if(isset($_POST['submit_retur_admin']) && wp_verify_nonce($_POST['retur_admin_nonce'], 'creare_retur_admin')) {
        $order_id = intval($_POST['order_id']);
        $product_id = intval($_POST['product_id']);
        $qty = intval($_POST['qty_retur']);
        $tip = sanitize_text_field($_POST['tip_retur']);
        $motiv = sanitize_textarea_field($_POST['motiv_retur']);
        $nota_interna = sanitize_textarea_field($_POST['nota_interna']);
        
        $order = wc_get_order($order_id);
        if($order) {
            $customer_id = $order->get_customer_id();
            
            $retur_id = wp_insert_post(array(
                'post_type' => 'cerere_retur',
                'post_title' => 'Retur #' . $order_id . ' - Admin - ' . date('Y-m-d H:i'),
                'post_status' => 'publish',
                'post_author' => $customer_id ?: get_current_user_id()
            ));
            
            if($retur_id) {
                update_post_meta($retur_id, '_order_id', $order_id);
                update_post_meta($retur_id, '_product_id', $product_id);
                update_post_meta($retur_id, '_qty_retur', $qty);
                update_post_meta($retur_id, '_tip_retur', $tip);
                update_post_meta($retur_id, '_motiv_retur', $motiv);
                update_post_meta($retur_id, '_status_retur', 'nou');
                update_post_meta($retur_id, '_customer_id', $customer_id);
                update_post_meta($retur_id, '_creat_de_admin', get_current_user_id());
                
                if($nota_interna) {
                    update_post_meta($retur_id, '_nota_interna', $nota_interna);
                }
                
                $message = 'Returul a fost creat cu succes! <a href="' . admin_url('post.php?post=' . $retur_id . '&action=edit') . '">Vezi cererea</a>';
                $message_type = 'success';
            }
        } else {
            $message = 'Comanda nu a fost găsită.';
            $message_type = 'error';
        }
    }
    ?>
    <div class="wrap">
        <h1>↩️ Creare Retur pentru Client</h1>
        
        <?php if($message): ?>
            <div class="notice notice-<?php echo $message_type; ?>"><p><?php echo $message; ?></p></div>
        <?php endif; ?>
        
        <form method="post" style="max-width: 600px; background: #fff; padding: 25px; border-radius: 10px; margin-top: 20px;">
            <?php wp_nonce_field('creare_retur_admin', 'retur_admin_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th>Căutare Client *</th>
                    <td>
                        <input type="text" id="search_client_retur" style="width: 250px;" placeholder="Telefon, email sau nr. comandă">
                        <button type="button" id="btn_cauta_client_retur" class="button">🔍 Caută</button>
                        <p class="description">Introdu telefonul, emailul sau nr. comenzii</p>
                    </td>
                </tr>
                <tr id="row_orders_retur" style="display:none;">
                    <th>Comandă *</th>
                    <td>
                        <select name="order_id" id="order_id_retur" required style="width: 100%;">
                            <option value="">-- Selectează comanda --</option>
                        </select>
                    </td>
                </tr>
                <tr id="row_client_info_retur" style="display:none;">
                    <th>Client</th>
                    <td><div id="client_info_retur" style="background:#f9f9f9; padding:10px; border-radius:5px;"></div></td>
                </tr>
                <tr id="row_product_retur" style="display:none;">
                    <th>Produs *</th>
                    <td>
                        <select name="product_id" id="product_id_retur" required style="width: 100%;">
                            <option value="">-- Selectează produs --</option>
                        </select>
                    </td>
                </tr>
                <tr id="row_qty_retur" style="display:none;">
                    <th>Cantitate *</th>
                    <td>
                        <select name="qty_retur" id="qty_retur" required style="width: 100px;">
                            <option value="1">1</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>Tip Retur *</th>
                    <td>
                        <select name="tip_retur" required>
                            <option value="">-- Selectează --</option>
                            <option value="defect">Produs defect</option>
                            <option value="gresit">Produs greșit livrat</option>
                            <option value="nemultumit">Client nemulțumit</option>
                            <option value="altul">Alt motiv</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>Motiv Client *</th>
                    <td>
                        <textarea name="motiv_retur" required rows="3" style="width: 100%;" placeholder="Ce a spus clientul..."></textarea>
                    </td>
                </tr>
                <tr>
                    <th>Notă Internă</th>
                    <td>
                        <textarea name="nota_interna" rows="2" style="width: 100%;" placeholder="Note pentru echipă (nu vede clientul)"></textarea>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" name="submit_retur_admin" class="button button-primary button-large">Creează Retur</button>
            </p>
        </form>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Căutare client pentru retur
        $('#btn_cauta_client_retur').on('click', function() {
            var search = $('#search_client_retur').val().trim();
            if(!search) {
                alert('Introdu un termen de căutare');
                return;
            }
            
            $(this).text('Se caută...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: { action: 'admin_search_orders', search: search },
                success: function(response) {
                    $('#btn_cauta_client_retur').html('🔍 Caută');
                    if(response.success && response.data.length > 0) {
                        var options = '<option value="">-- Selectează comanda --</option>';
                        $.each(response.data, function(i, o) {
                            options += '<option value="' + o.id + '">#' + o.id + ' | ' + o.date + ' | ' + o.customer + ' | ' + o.phone + ' | ' + o.total + '</option>';
                        });
                        $('#order_id_retur').html(options);
                        $('#row_orders_retur').show();
                        $('#row_client_info_retur, #row_product_retur, #row_qty_retur').hide();
                    } else {
                        alert('Nu s-au găsit comenzi pentru: ' + search);
                    }
                },
                error: function() {
                    $('#btn_cauta_client_retur').html('🔍 Caută');
                    alert('Eroare la căutare');
                }
            });
        });
        
        // Enter pentru căutare
        $('#search_client_retur').on('keypress', function(e) {
            if(e.which === 13) {
                e.preventDefault();
                $('#btn_cauta_client_retur').click();
            }
        });
        
        // Când selectează comanda
        $('#order_id_retur').on('change', function() {
            var orderId = $(this).val();
            if(!orderId) return;
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: { action: 'admin_get_order_info', order_id: orderId },
                success: function(response) {
                    if(response.success) {
                        var d = response.data;
                        $('#client_info_retur').html('<strong>' + d.customer_name + '</strong><br>📧 ' + d.customer_email + '<br>📱 ' + d.customer_phone + '<br>📅 Comandă: ' + d.order_date + ' | Livrare: ' + d.delivery_date);
                        $('#row_client_info_retur').show();
                        
                        var options = '<option value="">-- Selectează produs --</option>';
                        $.each(d.products, function(i, p) {
                            options += '<option value="' + p.id + '" data-qty="' + p.qty + '">' + p.name + ' (x' + p.qty + ')</option>';
                        });
                        $('#product_id_retur').html(options);
                        $('#row_product_retur').show();
                    }
                }
            });
        });
        
        // Când selectează produsul
        $('#product_id_retur').on('change', function() {
            var qty = $(this).find(':selected').data('qty') || 1;
            var options = '';
            for(var i = 1; i <= qty; i++) {
                options += '<option value="' + i + '">' + i + '</option>';
            }
            $('#qty_retur').html(options);
            $('#row_qty_retur').show();
        });
    });
    </script>
    <?php
}

// =============================================
// PAGINĂ CREARE GARANȚIE
// =============================================

function render_creare_garantie_page() {
    $message = '';
    $message_type = '';
    
    if(isset($_POST['submit_garantie_admin']) && wp_verify_nonce($_POST['garantie_admin_nonce'], 'creare_garantie_admin')) {
        $order_id = intval($_POST['order_id']);
        $product_id = intval($_POST['product_id']);
        $qty = intval($_POST['qty_garantie']);
        $descriere = sanitize_textarea_field($_POST['descriere_problema']);
        $nota_interna = sanitize_textarea_field($_POST['nota_interna']);
        
        $order = wc_get_order($order_id);
        if($order) {
            $customer_id = $order->get_customer_id();
            
            $garantie_id = wp_insert_post(array(
                'post_type' => 'cerere_garantie',
                'post_title' => 'Garanție #' . $order_id . ' - Admin - ' . date('Y-m-d H:i'),
                'post_status' => 'publish',
                'post_author' => $customer_id ?: get_current_user_id()
            ));
            
            if($garantie_id) {
                update_post_meta($garantie_id, '_order_id', $order_id);
                update_post_meta($garantie_id, '_product_id', $product_id);
                update_post_meta($garantie_id, '_qty_garantie', $qty);
                update_post_meta($garantie_id, '_descriere_problema', $descriere);
                update_post_meta($garantie_id, '_status_garantie', 'nou');
                update_post_meta($garantie_id, '_customer_id', $customer_id);
                update_post_meta($garantie_id, '_creat_de_admin', get_current_user_id());
                
                if($nota_interna) {
                    update_post_meta($garantie_id, '_nota_interna', $nota_interna);
                }
                
                $message = 'Garanția a fost creată cu succes! <a href="' . admin_url('post.php?post=' . $garantie_id . '&action=edit') . '">Vezi cererea</a>';
                $message_type = 'success';
            }
        } else {
            $message = 'Comanda nu a fost găsită.';
            $message_type = 'error';
        }
    }
    ?>
    <div class="wrap">
        <h1>🛡️ Creare Garanție pentru Client</h1>
        
        <?php if($message): ?>
            <div class="notice notice-<?php echo $message_type; ?>"><p><?php echo $message; ?></p></div>
        <?php endif; ?>
        
        <form method="post" style="max-width: 600px; background: #fff; padding: 25px; border-radius: 10px; margin-top: 20px;">
            <?php wp_nonce_field('creare_garantie_admin', 'garantie_admin_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th>Căutare Client *</th>
                    <td>
                        <input type="text" id="search_client_garantie" style="width: 250px;" placeholder="Telefon, email sau nr. comandă">
                        <button type="button" id="btn_cauta_client_garantie" class="button">🔍 Caută</button>
                        <p class="description">Introdu telefonul, emailul sau nr. comenzii</p>
                    </td>
                </tr>
                <tr id="row_orders_garantie" style="display:none;">
                    <th>Comandă *</th>
                    <td>
                        <select name="order_id" id="order_id_garantie" required style="width: 100%;">
                            <option value="">-- Selectează comanda --</option>
                        </select>
                    </td>
                </tr>
                <tr id="row_client_info_garantie" style="display:none;">
                    <th>Client</th>
                    <td><div id="client_info_garantie" style="background:#f9f9f9; padding:10px; border-radius:5px;"></div></td>
                </tr>
                <tr id="row_product_garantie" style="display:none;">
                    <th>Produs *</th>
                    <td>
                        <select name="product_id" id="product_id_garantie" required style="width: 100%;">
                            <option value="">-- Selectează produs --</option>
                        </select>
                    </td>
                </tr>
                <tr id="row_garantie_info" style="display:none;">
                    <th>Status Garanție</th>
                    <td><div id="garantie_info" style="padding:10px; border-radius:5px;"></div></td>
                </tr>
                <tr id="row_qty_garantie" style="display:none;">
                    <th>Cantitate *</th>
                    <td>
                        <select name="qty_garantie" id="qty_garantie" required style="width: 100px;">
                            <option value="1">1</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>Descriere Problemă *</th>
                    <td>
                        <textarea name="descriere_problema" required rows="3" style="width: 100%;" placeholder="Ce problemă are produsul..."></textarea>
                    </td>
                </tr>
                <tr>
                    <th>Notă Internă</th>
                    <td>
                        <textarea name="nota_interna" rows="2" style="width: 100%;" placeholder="Note pentru echipă (nu vede clientul)"></textarea>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" name="submit_garantie_admin" class="button button-primary button-large">Creează Garanție</button>
            </p>
        </form>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Căutare client pentru garanție
        $('#btn_cauta_client_garantie').on('click', function() {
            var search = $('#search_client_garantie').val().trim();
            if(!search) {
                alert('Introdu un termen de căutare');
                return;
            }
            
            $(this).text('Se caută...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: { action: 'admin_search_orders', search: search },
                success: function(response) {
                    $('#btn_cauta_client_garantie').html('🔍 Caută');
                    if(response.success && response.data.length > 0) {
                        var options = '<option value="">-- Selectează comanda --</option>';
                        $.each(response.data, function(i, o) {
                            options += '<option value="' + o.id + '">#' + o.id + ' | ' + o.date + ' | ' + o.customer + ' | ' + o.phone + ' | ' + o.total + '</option>';
                        });
                        $('#order_id_garantie').html(options);
                        $('#row_orders_garantie').show();
                        $('#row_client_info_garantie, #row_product_garantie, #row_garantie_info, #row_qty_garantie').hide();
                    } else {
                        alert('Nu s-au găsit comenzi pentru: ' + search);
                    }
                },
                error: function() {
                    $('#btn_cauta_client_garantie').html('🔍 Caută');
                    alert('Eroare la căutare');
                }
            });
        });
        
        // Enter pentru căutare
        $('#search_client_garantie').on('keypress', function(e) {
            if(e.which === 13) {
                e.preventDefault();
                $('#btn_cauta_client_garantie').click();
            }
        });
        
        // Când selectează comanda
        $('#order_id_garantie').on('change', function() {
            var orderId = $(this).val();
            if(!orderId) return;
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: { action: 'admin_get_order_info', order_id: orderId },
                success: function(response) {
                    if(response.success) {
                        var d = response.data;
                        $('#client_info_garantie').html('<strong>' + d.customer_name + '</strong><br>📧 ' + d.customer_email + '<br>📱 ' + d.customer_phone + '<br>📅 Comandă: ' + d.order_date + ' | Livrare: ' + d.delivery_date);
                        $('#row_client_info_garantie').show();
                        
                        var options = '<option value="">-- Selectează produs --</option>';
                        $.each(d.products, function(i, p) {
                            var statusText = p.in_garantie ? '✅ În garanție' : '❌ Expirat';
                            options += '<option value="' + p.id + '" data-qty="' + p.qty + '" data-garantie="' + p.garantie_luni + '" data-expira="' + p.data_expirare + '" data-valid="' + (p.in_garantie ? '1' : '0') + '">' + p.name + ' (' + p.garantie_luni + ' luni - ' + statusText + ')</option>';
                        });
                        $('#product_id_garantie').html(options);
                        $('#row_product_garantie').show();
                    }
                }
            });
        });
        
        // Când selectează produsul
        $('#product_id_garantie').on('change', function() {
            var selected = $(this).find(':selected');
            var qty = selected.data('qty') || 1;
            var garantie = selected.data('garantie');
            var expira = selected.data('expira');
            var valid = selected.data('valid');
            
            if(garantie) {
                var bgColor = valid == '1' ? '#d4edda' : '#f8d7da';
                var textColor = valid == '1' ? '#155724' : '#721c24';
                var statusText = valid == '1' ? '✅ Produsul este în garanție' : '⚠️ Garanția a expirat';
                $('#garantie_info').html('<strong>' + statusText + '</strong><br>Perioadă: ' + garantie + ' luni<br>Expiră la: ' + expira).css({'background': bgColor, 'color': textColor});
                $('#row_garantie_info').show();
            }
            
            var options = '';
            for(var i = 1; i <= qty; i++) {
                options += '<option value="' + i + '">' + i + '</option>';
            }
            $('#qty_garantie').html(options);
            $('#row_qty_garantie').show();
        });
    });
    </script>
    <?php
}

// =============================================
// PAGINĂ CĂUTARE CLIENT
// =============================================

function render_cautare_client_page() {
    $results = null;
    $search_term = '';
    
    if(isset($_POST['search_client']) && !empty($_POST['search_term'])) {
        $search_term = sanitize_text_field($_POST['search_term']);
        
        // Caută în useri
        $users = get_users(array(
            'search' => '*' . $search_term . '*',
            'search_columns' => array('user_login', 'user_email', 'display_name')
        ));
        
        // Caută și în comenzi (pentru guest orders sau după telefon)
        global $wpdb;
        
        // Caută după telefon
        $order_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
             WHERE (meta_key = '_billing_phone' OR meta_key = '_billing_email')
             AND meta_value LIKE %s 
             ORDER BY post_id DESC 
             LIMIT 30",
            '%' . $wpdb->esc_like($search_term) . '%'
        ));
        
        $orders = array();
        if(!empty($order_ids)) {
            $orders = array_map('wc_get_order', array_unique($order_ids));
            $orders = array_filter($orders);
        }
        
        $results = array('users' => $users, 'orders' => $orders);
    }
    ?>
    <div class="wrap">
        <h1>🔍 Căutare Client</h1>
        
        <form method="post" style="margin: 20px 0; background:#fff; padding:20px; border-radius:10px; display:inline-block;">
            <input type="text" name="search_term" value="<?php echo esc_attr($search_term); ?>" placeholder="Telefon, email sau nume..." style="width: 300px; padding: 10px; font-size: 15px;">
            <button type="submit" name="search_client" class="button button-primary" style="padding: 10px 20px;">🔍 Caută</button>
        </form>
        
        <?php if($results): ?>
            <?php if(!empty($results['users'])): ?>
                <h3>👥 Clienți înregistrați</h3>
                <table class="wp-list-table widefat fixed striped" style="margin-bottom:30px;">
                    <thead>
                        <tr>
                            <th>Nume</th>
                            <th>Email</th>
                            <th>Comenzi</th>
                            <th>Retururi</th>
                            <th>Garanții</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($results['users'] as $user): 
                            $order_count = wc_get_customer_order_count($user->ID);
                            $retur_count = count(get_posts(array('post_type' => 'cerere_retur', 'author' => $user->ID, 'numberposts' => -1)));
                            $garantie_count = count(get_posts(array('post_type' => 'cerere_garantie', 'author' => $user->ID, 'numberposts' => -1)));
                        ?>
                            <tr>
                                <td><strong><?php echo esc_html($user->display_name); ?></strong></td>
                                <td><?php echo esc_html($user->user_email); ?></td>
                                <td><?php echo $order_count; ?></td>
                                <td><?php echo $retur_count; ?></td>
                                <td><?php echo $garantie_count; ?></td>
                                <td>
                                    <a href="<?php echo admin_url('edit.php?post_type=shop_order&_customer_user=' . $user->ID); ?>" class="button button-small">Vezi Comenzi</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <?php if(!empty($results['orders'])): ?>
                <h3>📦 Comenzi găsite</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width:80px;">Comandă</th>
                            <th>Client</th>
                            <th>Email</th>
                            <th>Telefon</th>
                            <th>Data</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($results['orders'] as $order): if(!$order) continue; ?>
                            <tr>
                                <td><strong>#<?php echo $order->get_order_number(); ?></strong></td>
                                <td><?php echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()); ?></td>
                                <td><?php echo esc_html($order->get_billing_email()); ?></td>
                                <td><?php echo esc_html($order->get_billing_phone()); ?></td>
                                <td><?php echo $order->get_date_created()->format('d.m.Y'); ?></td>
                                <td><?php echo $order->get_total() . ' ' . $order->get_currency(); ?></td>
                                <td><?php echo wc_get_order_status_name($order->get_status()); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('post.php?post=' . $order->get_id() . '&action=edit'); ?>" class="button button-small">Vezi</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <?php if(empty($results['users']) && empty($results['orders'])): ?>
                <div style="background:#fff; padding:20px; border-radius:10px; margin-top:20px;">
                    <p>❌ Nu s-au găsit rezultate pentru "<strong><?php echo esc_html($search_term); ?></strong>"</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}

// =============================================
// AJAX - CĂUTARE COMENZI DUPĂ TELEFON/EMAIL
// =============================================
add_action('wp_ajax_admin_search_orders', function() {
    if(!current_user_can('edit_shop_orders')) {
        wp_send_json_error('Neautorizat');
    }
    
    $search = sanitize_text_field($_POST['search']);
    $orders = array();
    
    // Încearcă mai întâi ca nr. comandă
    if(is_numeric($search)) {
        $order = wc_get_order(intval($search));
        if($order) {
            wp_send_json_success(array(array(
                'id' => $order->get_id(),
                'date' => $order->get_date_created()->format('d.m.Y'),
                'customer' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'phone' => $order->get_billing_phone(),
                'email' => $order->get_billing_email(),
                'total' => $order->get_total() . ' ' . $order->get_currency(),
                'status' => wc_get_order_status_name($order->get_status())
            )));
        }
    }
    
    // Caută în toate comenzile
    $all_orders = wc_get_orders(array(
        'limit' => 500,
        'orderby' => 'date',
        'order' => 'DESC',
        'return' => 'objects'
    ));
    
    foreach($all_orders as $order) {
        $phone = $order->get_billing_phone();
        $email = $order->get_billing_email();
        $name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        
        // Verifică dacă search-ul se potrivește cu telefonul, emailul sau numele
        $phone_clean = preg_replace('/[^0-9]/', '', $phone);
        $search_clean = preg_replace('/[^0-9]/', '', $search);
        
        $match = false;
        
        // Potrivire telefon (ultimele cifre sau conține)
        if(!empty($search_clean) && (strpos($phone_clean, $search_clean) !== false)) {
            $match = true;
        }
        
        // Potrivire email
        if(stripos($email, $search) !== false) {
            $match = true;
        }
        
        // Potrivire nume
        if(stripos($name, $search) !== false) {
            $match = true;
        }
        
        if($match) {
            $orders[] = array(
                'id' => $order->get_id(),
                'date' => $order->get_date_created()->format('d.m.Y'),
                'customer' => $name,
                'phone' => $phone,
                'email' => $email,
                'total' => $order->get_total() . ' ' . $order->get_currency(),
                'status' => wc_get_order_status_name($order->get_status())
            );
        }
        
        // Limitează la 20 rezultate
        if(count($orders) >= 20) break;
    }
    
    if(empty($orders)) {
        wp_send_json_error('Nu s-au găsit comenzi');
    }
    
    wp_send_json_success($orders);
});

// =============================================
// AJAX - INFO COMANDĂ
// =============================================

add_action('wp_ajax_admin_get_order_info', function() {
    if(!current_user_can('edit_shop_orders')) {
        wp_send_json_error('Neautorizat');
    }
    
    $order_id = intval($_POST['order_id']);
    $order = wc_get_order($order_id);
    
    if(!$order) {
        wp_send_json_error('Comanda nu există');
    }
    
    $delivery_date = $order->get_date_completed() ? $order->get_date_completed()->getTimestamp() : $order->get_date_created()->getTimestamp();
    
    $products = array();
    foreach($order->get_items() as $item) {
        $product_id = $item->get_product_id();
        $garantie_luni = get_post_meta($product_id, '_perioada_garantie', true) ?: 12;
        $data_expirare = date('d.m.Y', strtotime('+' . $garantie_luni . ' months', $delivery_date));
        $in_garantie = time() < strtotime('+' . $garantie_luni . ' months', $delivery_date);
        
        $products[] = array(
            'id' => $product_id,
            'name' => $item->get_name(),
            'qty' => $item->get_quantity(),
            'garantie_luni' => $garantie_luni,
            'data_expirare' => $data_expirare,
            'in_garantie' => $in_garantie
        );
    }
    
    wp_send_json_success(array(
        'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
        'customer_email' => $order->get_billing_email(),
        'customer_phone' => $order->get_billing_phone(),
        'order_date' => $order->get_date_created()->format('d.m.Y'),
        'delivery_date' => date('d.m.Y', $delivery_date),
        'products' => $products
    ));
});

// =============================================
// AFIȘARE NOTĂ INTERNĂ ÎN ADMIN
// =============================================

add_action('add_meta_boxes', function() {
    add_meta_box('nota_interna_retur', '📝 Notă Internă', 'render_nota_interna_metabox', 'cerere_retur', 'side', 'default');
    add_meta_box('nota_interna_garantie', '📝 Notă Internă', 'render_nota_interna_metabox', 'cerere_garantie', 'side', 'default');
}, 20);

function render_nota_interna_metabox($post) {
    $nota = get_post_meta($post->ID, '_nota_interna', true);
    $creat_de = get_post_meta($post->ID, '_creat_de_admin', true);
    ?>
    <textarea name="nota_interna" style="width:100%; height:80px;" placeholder="Note vizibile doar pentru echipă..."><?php echo esc_textarea($nota); ?></textarea>
    <?php if($creat_de): 
        $admin = get_user_by('id', $creat_de);
    ?>
        <p style="margin-top:10px; padding:8px; background:#fff3cd; border-radius:4px; font-size:12px;">
            ⚠️ Creat de admin: <strong><?php echo $admin ? $admin->display_name : 'Necunoscut'; ?></strong>
        </p>
    <?php endif; ?>
    <?php
}

// Salvare notă internă
add_action('save_post_cerere_retur', function($post_id) {
    if(isset($_POST['nota_interna'])) {
        update_post_meta($post_id, '_nota_interna', sanitize_textarea_field($_POST['nota_interna']));
    }
});

add_action('save_post_cerere_garantie', function($post_id) {
    if(isset($_POST['nota_interna'])) {
        update_post_meta($post_id, '_nota_interna', sanitize_textarea_field($_POST['nota_interna']));
    }
});

// =============================================
// PAGINĂ TRANSFORMARE PF → PJ
// =============================================

function render_transformare_pf_pj_page() {
    ?>
    <div class="wrap">
        <h1>🔄 Transformare Cont PF → PJ</h1>
        <p style="color: #666; margin-bottom: 20px;">
            Transformă un cont de persoană fizică (PF) în persoană juridică (PJ) pentru a accesa prețurile B2B.
        </p>
        
        <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h2 style="margin-top: 0;">Căutare utilizator</h2>
            
            <form id="search-user-form" style="margin-bottom: 20px;">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="search_query">Email, nume sau telefon</label>
                        </th>
                        <td>
                            <input type="text" id="search_query" name="search_query" class="regular-text" placeholder="ex: client@example.com sau 0721234567" required />
                            <p class="description">Caută utilizatorul după email, nume sau număr de telefon.</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">🔍 Caută utilizator</button>
                </p>
            </form>
            
            <div id="user-results" style="display: none;">
                <hr style="margin: 20px 0;" />
                <h3>Rezultate căutare</h3>
                <div id="user-info" style="background: #f9f9f9; padding: 15px; border-radius: 4px; margin-bottom: 15px;"></div>
                
                <form id="transform-form">
                    <input type="hidden" id="user_id" name="user_id" />
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="billing_cui">CUI (opțional)</label>
                            </th>
                            <td>
                                <input type="text" id="billing_cui" name="billing_cui" class="regular-text" placeholder="RO12345678" />
                                <p class="description">CUI-ul firmei (poate fi completat ulterior).</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="billing_company">Denumire firmă (opțional)</label>
                            </th>
                            <td>
                                <input type="text" id="billing_company" name="billing_company" class="regular-text" placeholder="Nume firmă S.R.L." />
                                <p class="description">Denumirea completă a firmei.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label>
                                    <input type="checkbox" id="auto_approve_b2b" name="auto_approve_b2b" checked />
                                    Aprobare automată B2B
                                </label>
                            </th>
                            <td>
                                <p class="description">Dacă este bifat, utilizatorul va primi automat acces la prețurile B2B (fără așteptare aprobare).</p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary button-large">✅ Transformă în PJ</button>
                        <span id="transform-status" style="margin-left: 15px;"></span>
                    </p>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Căutare utilizator
        $('#search-user-form').on('submit', function(e) {
            e.preventDefault();
            
            var query = $('#search_query').val().trim();
            if (!query) {
                alert('Introduceți un email, nume sau telefon pentru căutare.');
                return;
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'webgsm_search_user_for_pj',
                    query: query,
                    nonce: '<?php echo wp_create_nonce('webgsm_search_user'); ?>'
                },
                success: function(response) {
                    if (response.success && response.data.user) {
                        var user = response.data.user;
                        var is_pj = user.is_pj === 'yes';
                        var b2b_status = user.b2b_status || 'none';
                        
                        var html = '<div style="display: flex; gap: 20px; align-items: start;">';
                        html += '<div style="flex: 1;">';
                        html += '<h4 style="margin-top: 0;">' + user.display_name + '</h4>';
                        html += '<p><strong>Email:</strong> ' + user.email + '</p>';
                        html += '<p><strong>Telefon:</strong> ' + (user.phone || 'N/A') + '</p>';
                        html += '<p><strong>Tip cont:</strong> <span style="color: ' + (is_pj ? '#22c55e' : '#ef4444') + '; font-weight: bold;">' + (is_pj ? 'PJ (Persoană Juridică)' : 'PF (Persoană Fizică)') + '</span></p>';
                        html += '<p><strong>Status B2B:</strong> ' + (b2b_status === 'approved' ? '<span style="color: #22c55e;">✓ Aprobat</span>' : b2b_status === 'pending' ? '<span style="color: #f59e0b;">⏳ În așteptare</span>' : '<span style="color: #6b7280;">— Nu este B2B</span>') + '</p>';
                        html += '</div>';
                        html += '</div>';
                        
                        $('#user-info').html(html);
                        $('#user_id').val(user.ID);
                        $('#user-results').show();
                        
                        if (is_pj) {
                            $('#transform-form').html('<div style="background: #d1fae5; border: 1px solid #22c55e; padding: 15px; border-radius: 4px; color: #15803d;"><strong>ℹ️ Acest utilizator este deja PJ.</strong></div>');
                        }
                    } else {
                        alert('Utilizatorul nu a fost găsit. Verificați email-ul, numele sau telefonul.');
                        $('#user-results').hide();
                    }
                },
                error: function() {
                    alert('Eroare la căutare. Vă rugăm să încercați din nou.');
                }
            });
        });
        
        // Transformare PF → PJ
        $('#transform-form').on('submit', function(e) {
            e.preventDefault();
            
            var userId = $('#user_id').val();
            if (!userId) {
                alert('Vă rugăm să căutați mai întâi un utilizator.');
                return;
            }
            
            var data = {
                action: 'webgsm_transform_pf_to_pj',
                user_id: userId,
                billing_cui: $('#billing_cui').val(),
                billing_company: $('#billing_company').val(),
                auto_approve_b2b: $('#auto_approve_b2b').is(':checked') ? 1 : 0,
                nonce: '<?php echo wp_create_nonce('webgsm_transform_pj'); ?>'
            };
            
            $('#transform-status').html('<span style="color: #f59e0b;">⏳ Se procesează...</span>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        $('#transform-status').html('<span style="color: #22c55e;">✓ ' + response.data.message + '</span>');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        $('#transform-status').html('<span style="color: #ef4444;">✗ ' + (response.data.message || 'Eroare la transformare') + '</span>');
                    }
                },
                error: function() {
                    $('#transform-status').html('<span style="color: #ef4444;">✗ Eroare la comunicare cu serverul.</span>');
                }
            });
        });
    });
    </script>
    <?php
}

// =============================================
// AJAX HANDLERS
// =============================================

// Căutare utilizator pentru transformare
add_action('wp_ajax_webgsm_search_user_for_pj', function() {
    check_ajax_referer('webgsm_search_user', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permisiuni insuficiente'));
        return;
    }
    
    $query = sanitize_text_field($_POST['query']);
    if (empty($query)) {
        wp_send_json_error(array('message' => 'Query gol'));
        return;
    }
    
    // Caută după email
    $user = get_user_by('email', $query);
    
    // Dacă nu găsește după email, caută după login/nume
    if (!$user) {
        $users = get_users(array(
            'search' => '*' . esc_attr($query) . '*',
            'search_columns' => array('user_login', 'user_nicename', 'display_name'),
            'number' => 1
        ));
        if (!empty($users)) {
            $user = $users[0];
        }
    }
    
    // Dacă tot nu găsește, caută după telefon în meta
    if (!$user) {
        $users = get_users(array(
            'meta_key' => 'billing_phone',
            'meta_value' => $query,
            'number' => 1
        ));
        if (!empty($users)) {
            $user = $users[0];
        }
    }
    
    if (!$user) {
        wp_send_json_error(array('message' => 'Utilizator negăsit'));
        return;
    }
    
    $user_data = array(
        'ID' => $user->ID,
        'display_name' => $user->display_name,
        'email' => $user->user_email,
        'phone' => get_user_meta($user->ID, 'billing_phone', true),
        'is_pj' => get_user_meta($user->ID, '_is_pj', true),
        'b2b_status' => get_user_meta($user->ID, '_b2b_status', true),
        'billing_cui' => get_user_meta($user->ID, 'billing_cui', true),
        'billing_company' => get_user_meta($user->ID, 'billing_company', true)
    );
    
    wp_send_json_success(array('user' => $user_data));
});

// Transformare PF → PJ
add_action('wp_ajax_webgsm_transform_pf_to_pj', function() {
    check_ajax_referer('webgsm_transform_pj', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permisiuni insuficiente'));
        return;
    }
    
    $user_id = intval($_POST['user_id']);
    if (!$user_id) {
        wp_send_json_error(array('message' => 'ID utilizator invalid'));
        return;
    }
    
    $user = get_userdata($user_id);
    if (!$user) {
        wp_send_json_error(array('message' => 'Utilizator negăsit'));
        return;
    }
    
    // Verifică dacă e deja PJ
    $is_pj = get_user_meta($user_id, '_is_pj', true);
    if ($is_pj === 'yes') {
        wp_send_json_error(array('message' => 'Utilizatorul este deja PJ'));
        return;
    }
    
    // Setează ca PJ
    update_user_meta($user_id, '_is_pj', 'yes');
    update_user_meta($user_id, '_tip_client', 'pj');
    update_user_meta($user_id, 'webgsm_customer_type', 'pj');
    
    // Actualizează CUI și denumire firmă dacă sunt furnizate
    if (!empty($_POST['billing_cui'])) {
        update_user_meta($user_id, 'billing_cui', sanitize_text_field($_POST['billing_cui']));
    }
    if (!empty($_POST['billing_company'])) {
        update_user_meta($user_id, 'billing_company', sanitize_text_field($_POST['billing_company']));
    }
    
    // Aprobare automată B2B dacă e cerută
    $auto_approve = isset($_POST['auto_approve_b2b']) && $_POST['auto_approve_b2b'] == '1';
    if ($auto_approve) {
        if (class_exists('WebGSM_B2B_Approval_System')) {
            $approval_system = new WebGSM_B2B_Approval_System();
            $approval_system->approve_account($user_id);
        } else {
            // Fallback dacă clasa nu există
            update_user_meta($user_id, '_b2b_status', 'approved');
            update_user_meta($user_id, '_b2b_approved_date', current_time('mysql'));
            $user->add_role('b2b_customer');
            $user->add_role('customer');
        }
    }
    
    // Log acțiunea
    $admin_user = wp_get_current_user();
    update_user_meta($user_id, '_pf_to_pj_converted_by', $admin_user->ID);
    update_user_meta($user_id, '_pf_to_pj_converted_date', current_time('mysql'));
    
    $message = 'Utilizatorul ' . $user->display_name . ' (' . $user->user_email . ') a fost transformat cu succes în PJ.';
    if ($auto_approve) {
        $message .= ' Status B2B: Aprobat automat.';
    }
    
    wp_send_json_success(array('message' => $message));
});
