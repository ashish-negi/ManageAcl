<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.View.Layouts
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

$cakeDescription = __d('cake_dev', 'CakePHP: the rapid development php framework');
$cakeVersion = __d('cake_dev', 'CakePHP %s', Configure::version())
?>
<!DOCTYPE html>
<html>
<head>
	<?php echo $this->Html->charset(); ?>
	<title><?php echo $titleForLayout?> </title>
	<?php
		echo $this->Html->meta('icon');

		echo $this->Html->css('cake.generic');

		echo $this->fetch('meta');
		
		echo $this->fetch('script');
	?>
    <style>
        .manageacl-ident-2, .manageacl-ident-1{display: none;}
         select { font-size: 100%;}
        .head-txt, .show-row{text-decoration: none;}
        .manageacl-ident-0 a{color: #000;}
        .manageacl-ident-1 a{color: #0082bf;}
        .manageacl-ident-2{color: #75767a;}
        .span-plus:before{content: "\2b"; margin:0px 5px}
        .span-minus:before{content: "\2212"; margin:0px 5px}
        .manageacl-ident-1 a span:before{margin:0px -4px 0px 8px;}
    </style>
</head>
<body>
    <div id="container">
        <div id="header">

        </div>
        <div id="content">

                <?php echo $this->Session->flash(); ?>

                <?php echo $this->fetch('content'); ?>
        </div>
        <div id="footer">


        </div>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script>
        $(document).ready(function(){
            $('a.show-row').click(function(){
                var dataParentId = $(this).parents('tr').attr('data-id');
                $(this).parents('tr').nextAll("tr[data-parent-id="+dataParentId+"]").toggle();
                $(this).children('span').toggleClass('span-minus');
                if(dataParentId == 1){
                    $(this).parents('tr').nextAll(".manageacl-ident-1").find('td a span').removeClass('span-minus').addClass('span-plus');
                    $(this).parents('tr').nextAll('.manageacl-ident-2').hide();
                }
            });
        });
    </script>
</body>
</html>
