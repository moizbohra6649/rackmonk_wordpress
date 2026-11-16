<div class="domain__price-list">
    <table class="table">
        <thead>
            <tr>
                <th scope="col"><?php echo esc_html($settings['column1_header']); ?></th>
                <th scope="col"><?php echo esc_html($settings['column2_header']); ?></th>
                <th scope="col"><?php echo esc_html($settings['column3_header']); ?></th>
            </tr>
        </thead>
            <tbody>
                <?php foreach ($settings['domain_pricings'] as $item) : ?>
                    <tr>
                        <td><?php echo esc_html($item['domain_extension']); ?></td>
                        <td><?php echo esc_html($item['transfer_fee']); ?></td>
                        <td><?php echo esc_html($item['registration_fee']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
</div>