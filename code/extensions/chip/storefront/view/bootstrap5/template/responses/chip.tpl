<form id="CHIPFrm" action="<?php echo $this->html->getURL('extension/chip/confirm');?>" method="get">
    <div class="form-group action-buttons">
        <div class="col-md-12">
            <button type="submit" id="checkout_btn" class="btn btn-primary" title="<?php echo $button_confirm->text ?>">
                <i class="fa fa-check"></i>
                <?php echo $button_confirm->text; ?>
            </button>
        </div>
    </div>
</form>
<script type="text/javascript">
    $('#CHIPFrm').on('submit',function(e) {
        e.preventDefault();
        $('body').css('cursor', 'wait');
        $.ajax({
            type: 'GET',
            url: '<?php echo $this->html->getSecureURL('r/extension/chip/confirm');?>',
            dataType: 'json',
            beforeSend: function () {
                $('.alert').remove();
                $('.action-buttons')
                    .hide()
                    .before('<div class="wait alert alert-info text-center"><i class="fa fa-refresh fa-spin"></i> <?php echo $text_wait; ?></div>');
            },
            success: function (data) {
              if (data.hasOwnProperty('checkout_url') ) {
                location = data.checkout_url;
              } else {
                alert(data.__all__[0].message);
                $('.wait').remove();
                $('.action-buttons').show();
                try {
                    resetLockBtn();
                } catch (e) {
                }
              }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                alert(textStatus + ' ' + errorThrown);
                $('.wait').remove();
                $('.action-buttons').show();
                try {
                    resetLockBtn();
                } catch (e) {
                }
            }
        });
    });
</script>
