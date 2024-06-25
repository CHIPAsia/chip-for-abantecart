<?php include($tpl_common_dir . 'action_confirm.tpl'); ?>

<div class="table-responsive">
  <?php if ($chip_data) { ?>
    <table class="table table-striped">
      <tr>
        <td>Purchase ID</td>
        <td>
          <a href="<?php echo $checkout_url; ?>" target="_blank">
            <?php echo $chip_data['id']; ?>
            <i class="fa fa-external-link fa-fw"></i>
          </a>
        </td>
      </tr>
      <tr>
      <tr>
        <td>Environment</td>
        <td>
          <?php echo $test_mode ? 'Staging' : 'Production'; ?>
        </td>
      </tr>
      <tr>
        <td>Total Paid</td>
        <td><?php echo $chip_data['purchase']['currency'] . ' ' . number_format($chip_data['payment']['amount'] / 100, 2); ?></td>
      </tr>
      <tr>
        <td>Payment Method</td>
        <td>
          <?php echo strtoupper($chip_data['transaction_data']['payment_method']); ?>
        </td>
      </tr>
      <tr>
        <td>Country</td>
        <td>
          <?php echo strtoupper($chip_data['transaction_data']['country']); ?>
        </td>
      </tr>
      <tr>
        <td>Status</td>
        <td>
          <?php echo ucfirst($chip_data['status']); ?>
        </td>
      </tr>
      <tr>
        <td>Total Paid</td>
        <td><?php echo $chip_data['purchase']['currency'] . ' ' . number_format($chip_data['payment']['amount'] / 100, 2); ?></td>
      </tr>
    </table>
  <?php } ?>
</div>

<script type="text/javascript">
  <?php if($test_mode) { ?>
  $(".tab-content").addClass('status_test');
  <?php } ?>
</script>