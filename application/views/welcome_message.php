<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<?php $this->load->view('header.php'); ?>
        <h1>Welcome to CodeIgniter!</h1>

        <div class="alert alert-success"><strong><?= $data ?></strong></div>

        <p>this is my change</p>

        <p class="footer">Page rendered in <strong>{elapsed_time}</strong> seconds. <?php echo  (ENVIRONMENT === 'development') ?  'CodeIgniter Version <strong>' . CI_VERSION . '</strong>' : '' ?></p>
    </body>
</html>