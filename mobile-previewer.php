<?php 

/**
 *
 * @package   Mobile Previewer
 * @author    Wallmander&Co <mikael@wallmanderco.se>
 *
 * @wordpress-plugin
 * Plugin Name: Mobile Previewer
 * Plugin URI:  
 * Description: Adds a small frame for logged in users at the bottom right of every page that shows the a resized version of the current page.
 * Version:     0.1.3
 * Author:      Wallmander&Co
 * Author URI:  http://wallmanderco.se
 */

if(function_exists('add_action'))
    add_action( 'after_setup_theme', 'mobile_previewer_init' );

function mobile_previewer_init(){
    global $post;
    if(current_user_can('edit_posts') || current_user_can('edit_pages')){
        wp_enqueue_script("jquery");
        add_action('wp_footer', 'mobile_previewer_wp_footer');
        add_action('wp_head', 'mobile_previewer_wp_head');
    }
}

function mobile_previewer_wp_head() {
    ?> 
<style>
#mobile-previewer-mini-device-preview,
#mobile-previewer-device-switcher,
#mobile-previewer-overlay{
    position: fixed;
    box-shadow: 0 0 10px rgba(0,0,0,0.2);
    z-index: 999;
    display: none; 
    box-sizing: content-box !important;
}

#mobile-previewer-mini-device-preview.mobile-previewer-loaded{
    display: block;
    position: fixed;
    bottom: 30px;
    right: 0;
    height: 480px;
    width: 320px;
    margin-right: 10px;
    overflow: hidden;
    transition: -webkit-transform 1s, -ms-transform 1s, transform 1s;

    -webkit-transform:scale(.75,.75);
    -ms-transform:scale(.75,.75);
    transform:scale(.75,.75);

    -webkit-transform-origin: 100% 100% 0;
    -ms-transform-origin: 100% 100% 0;
    transform-origin: 100% 100% 0;
}
#mobile-previewer-mini-device-preview:hover{
    -webkit-transform:scale(1,1) !important;
    -ms-transform:scale(1,1) !important;
    transform:scale(1,1) !important;
    right: 0;
    min-height: 90%;
    max-height: 90%;
    box-shadow: 0 0 15px rgba(0,0,0,0.2);
}

#mobile-previewer-device-switcher{ bottom: 0; right: 10px; width: 240px;
    font-size: 9px;  font-family: sans-serif; height: 20px; line-height: 20px; text-align: center;  background: #fff; }
#mobile-previewer-device-switcher a{ color: #777 !important; display: inline-block; margin: 0 4px; text-decoration: none; }
#mobile-previewer-device-switcher a.active, #mobile-previewer-device-switcher a:hover { text-decoration: underline;}
#mobile-previewer-overlay{ width: 100%; height: 100%; top: 0; left: 0; background: rgba(0,0,0,.1) }

html.mobile-previewer-hide-wpadminbar { margin-top: 0 !important; }
html.mobile-previewer-hide-wpadminbar #wpadminbar{ display: none;}
html.mobile-previewer-no-scoll{ overflow: hidden !important;}

</style>
    <?php
}

function mobile_previewer_wp_footer() {

    $format = get_user_meta(get_current_user_id(),'mobile_previewer_format',true);
    if(!$format)
        $format = 'none';
    ?>

    <div id="mobile-previewer-overlay"></div>
    <iframe id="mobile-previewer-mini-device-preview" src="" frameborder="0"></iframe>
    <div id="mobile-previewer-device-switcher">
        <a href="#" data-device="none"  <?php if($format == 'none') echo 'class="active"' ?>>none</a>
    	<a href="#" data-device="iphonep" <?php if($format == 'iphonep') echo 'class="active"' ?>>320x480</a>
    	<a href="#" data-device="iphonel" <?php if($format == 'iphonel') echo 'class="active"' ?>>320x480</a>
    	<a href="#" data-device="ipadp" <?php if($format == 'ipadp') echo 'class="active"' ?>>768x1024</a>
    	<a href="#" data-device="ipadl" <?php if($format == 'ipadl') echo 'class="active"' ?>>1024x768</a>
    </div>
    <script type="text/javascript">
    (function( $ ) {
    $(document).ready(function(){

        var formats = {
            iphonep : {
                width: 320,
                height: 480,
                ratio: .75
            },
            iphonel : {
                width: 480,
                height: 320,
                ratio: .5
            },
            ipadp : {
                width: 768,
                height: 1024,
                ratio: .3133
            },
            ipadl : {
                width: 1024,
                height: 768,
                ratio: .2333
            },
        };

    	var $devicePreview = $('#mobile-previewer-mini-device-preview');

    	var copyScoll = function(){
    		var percentScrolled = ($(window).scrollTop()) / ($(document).height()-$(window).height())
    		var phoneWindowHeight = $devicePreview.height();
    		var phoneDocumentHeight = $devicePreview.contents().height();
    		$devicePreview.contents().scrollTop((phoneDocumentHeight - phoneWindowHeight) * percentScrolled);
    	}

        var setFormat = function(formatName){
            if(formatName == 'none'){
                $devicePreview.hide();
            }else{
                $devicePreview.show();
                var format = formats[formatName];
                $devicePreview.width(format.width);
                $devicePreview.height(format.height);
                $devicePreview.css({'transform':'scale('+format.ratio+','+format.ratio+')'});

                copyScoll();
            }
        }

    	$('#mobile-previewer-device-switcher a').click(function(){
            formatName = $(this).attr('data-device');
            $(this).addClass('active').siblings().removeClass('active');
    		setFormat(formatName);
            $.post('<?php echo bloginfo('wpurl') ?>/wp-admin/admin-ajax.php', {action:'mobile_previewer_set_format', format: formatName});
    		return false;
    	});

	    if(window.self === window.top){
	    	$devicePreview.addClass('mobile-previewer-loaded').attr('src','http://<?php echo $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] ?>');
	    	$(window).scroll(copyScoll);
	    	$devicePreview.load(copyScoll)
		    	.mouseenter(function(){copyScoll(); setTimeout(copyScoll,1); $('#mobile-previewer-overlay').fadeIn(200);})
		    	.mouseleave(function(){copyScoll(); setTimeout(copyScoll,1); $('#mobile-previewer-overlay').fadeOut(200);});
		    $('#mobile-previewer-device-switcher').show();
	    }else{
			$('html').addClass('mobile-previewer-hide-wpadminbar').addClass('mobile-previewer-no-scoll');
		}

        setFormat('<?php echo $format ?>');
	});
    })(jQuery);
	</script>
    <?
}

if(function_exists('is_admin') && is_admin()){


    add_action('wp_ajax_mobile_previewer_set_format', 'ajax_mobile_previewer_set_format');

    function ajax_mobile_previewer_set_format() {
        $format = (isset($_POST['format'])) ? $_POST['format'] : 'none';
        update_user_meta( get_current_user_id(), 'mobile_previewer_format', $format);
        echo 1;
        die(); 
    }

}


