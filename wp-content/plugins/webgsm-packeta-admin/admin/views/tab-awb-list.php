<?php
if (!defined('ABSPATH')) {
    exit;
}

$awb_rows = WebGSM_Packeta_Awb_Repository::list_recent(150);
$steps = WebGSM_Packeta_Status_Mapper::STEPS;
$label_format = WebGSM_Packeta_Config::get_default_label_format();
?>
<div class="webgsm-packeta-card webgsm-packeta-card-wide">
    <h2>AWB-uri trimise</h2>
    <p class="webgsm-packeta-help">
        Lista AWB-urilor create din acest plugin. Statusul se actualizează automat la fiecare 60 secunde pentru coletele în curs.
        Pentru Sameday/Fan, eticheta PDF este cea a curierului (nu barcode-ul <code>Z …</code>).
    </p>

    <form method="post" class="webgsm-packeta-inline-form" style="margin-bottom:20px;">
        <?php wp_nonce_field('webgsm_packeta'); ?>
        <input type="hidden" name="tab" value="awb_list" />
        <input type="hidden" name="webgsm_packeta_action" value="register_awb" />
        <label><strong>Adaugă AWB existent</strong> (packet ID sau Z …)</label>
        <div class="webgsm-packeta-inline-row">
            <input type="text" name="register_packet_id" class="regular-text" placeholder="3832892743 sau Z 383 2892 743" required />
            <input type="text" name="register_order_ref" class="regular-text" placeholder="Referință comandă (opțional)" />
            <button type="submit" class="button">Adaugă și citește status</button>
        </div>
    </form>

    <p>
        <button type="button" class="button button-secondary" id="webgsm-packeta-refresh-all">
            Actualizează toate statusurile active
        </button>
        <span class="webgsm-packeta-sync-hint" id="webgsm-packeta-sync-hint"></span>
    </p>

    <?php if ($awb_rows === []) : ?>
        <p><em>Niciun AWB înregistrat încă. Creează unul din tab-ul „AWB nou” sau adaugă un packet ID existent mai sus.</em></p>
    <?php else : ?>
        <table class="widefat striped webgsm-packeta-awb-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>AWB / Destinatar</th>
                    <th>Curier</th>
                    <th>Status livrare</th>
                    <th>Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($awb_rows as $row) :
                    $packet_id = (string) ($row['packet_id'] ?? '');
                    $barcode = (string) ($row['barcode'] ?? '');
                    $step = (int) ($row['progress_step'] ?? 0);
                    $percent = (int) ($row['progress_percent'] ?? 0);
                    $is_problem = !empty($row['is_problem']);
                    $is_final = !empty($row['is_final']);
                    $status_label = (string) ($row['status_text'] ?? '');
                    if ($status_label === '' && !empty($row['status_code_text'])) {
                        $status_label = WebGSM_Packeta_Status_Mapper::label_for_code_text((string) $row['status_code_text']);
                    }
                    $recipient = trim((string) ($row['recipient_name'] ?? ''));
                    $courier = (string) ($row['courier_number'] ?? '');
                    $updated = (string) ($row['updated_at'] ?? '');
                    ?>
                    <tr class="webgsm-packeta-awb-row<?php echo $is_problem ? ' is-problem' : ''; ?><?php echo $is_final && !$is_problem ? ' is-delivered' : ''; ?>"
                        data-packet-id="<?php echo esc_attr($packet_id); ?>"
                        data-final="<?php echo $is_final ? '1' : '0'; ?>">
                        <td class="webgsm-packeta-awb-date">
                            <?php echo esc_html(wp_date('d.m.Y H:i', strtotime((string) ($row['created_at'] ?? '')))); ?>
                            <?php if ((string) ($row['order_ref'] ?? '') !== '') : ?>
                                <br><small><?php echo esc_html((string) $row['order_ref']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo esc_html($barcode !== '' ? $barcode : $packet_id); ?></strong>
                            <?php if ($barcode !== '' && $packet_id !== '') : ?>
                                <br><small>ID: <?php echo esc_html($packet_id); ?></small>
                            <?php endif; ?>
                            <?php if ($recipient !== '') : ?>
                                <br><?php echo esc_html($recipient); ?>
                                <?php if ((string) ($row['recipient_phone'] ?? '') !== '') : ?>
                                    <small> · <?php echo esc_html((string) $row['recipient_phone']); ?></small>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if ($courier !== '') : ?>
                                <br><small>AWB curier: <code><?php echo esc_html($courier); ?></code></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html((string) ($row['carrier_name'] ?? '—')); ?></td>
                        <td class="webgsm-packeta-awb-status-cell">
                            <div class="webgsm-packeta-track<?php echo $is_problem ? ' is-problem' : ''; ?>">
                                <div class="webgsm-packeta-track-steps">
                                    <?php foreach ($steps as $i => $s) :
                                        $cls = 'webgsm-packeta-track-step';
                                        if ($i < $step) {
                                            $cls .= ' is-done';
                                        } elseif ($i === $step && !$is_final) {
                                            $cls .= ' is-active';
                                        } elseif ($i === $step && $is_final && !$is_problem) {
                                            $cls .= ' is-done is-active';
                                        }
                                        ?>
                                        <span class="<?php echo esc_attr($cls); ?>" title="<?php echo esc_attr($s['label']); ?>">
                                            <span class="dot"></span>
                                            <span class="lbl"><?php echo esc_html($s['label']); ?></span>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                                <div class="webgsm-packeta-track-bar" aria-hidden="true">
                                    <div class="webgsm-packeta-track-fill" style="width:<?php echo esc_attr((string) $percent); ?>%"></div>
                                </div>
                                <div class="webgsm-packeta-track-meta">
                                    <span class="webgsm-packeta-status-label"><?php echo esc_html($status_label !== '' ? $status_label : '—'); ?></span>
                                    <?php if ($updated !== '') : ?>
                                        <span class="webgsm-packeta-status-updated">· <?php echo esc_html(wp_date('d.m. H:i', strtotime($updated))); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="webgsm-packeta-awb-actions">
                            <form method="post" class="webgsm-packeta-row-action">
                                <?php wp_nonce_field('webgsm_packeta'); ?>
                                <input type="hidden" name="tab" value="awb_list" />
                                <input type="hidden" name="webgsm_packeta_action" value="download_label" />
                                <input type="hidden" name="label_packet_id" value="<?php echo esc_attr($packet_id); ?>" />
                                <input type="hidden" name="label_format" value="<?php echo esc_attr($label_format); ?>" />
                                <button type="submit" class="button button-small">Etichetă PDF</button>
                            </form>
                            <form method="post" class="webgsm-packeta-row-action">
                                <?php wp_nonce_field('webgsm_packeta'); ?>
                                <input type="hidden" name="tab" value="awb_list" />
                                <input type="hidden" name="webgsm_packeta_action" value="courier_number" />
                                <input type="hidden" name="courier_packet_id" value="<?php echo esc_attr($packet_id); ?>" />
                                <button type="submit" class="button button-small">AWB curier</button>
                            </form>
                            <button type="button" class="button button-small webgsm-packeta-refresh-one" data-packet-id="<?php echo esc_attr($packet_id); ?>">
                                Status
                            </button>
                            <?php if ((string) ($row['shipment_id'] ?? '') === '') : ?>
                                <a class="button button-small"
                                   href="<?php echo esc_url(add_query_arg(['page' => 'webgsm-packeta', 'tab' => 'shipment', 'prefill_packet' => $packet_id], admin_url('admin.php'))); ?>">
                                    Expediție
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
