
<div class="formbold-main-wrapper">

  <!-- Author: FormBold Team -->
  <!-- Learn More: https://formbold.com -->
  <div class="formbold-form-wrapper">
    <img src="<?php echo esc_url(plugins_url('../banner.png', __FILE__ )); ?>" style="width: 100%;">
<div class="pad_all">
    <h2 style="text-align: center;">HTML WP</h2>
    <p style="text-align: center;">Upload Your HTML files in a zip file</p>
    <form action="#" method="POST">
      <div class="formbold-mb-5">
        <label for="theme-name" class="formbold-form-label">
          Theme Name:
        </label>
        <input
          type="text"
          name="theme-name"
          id="theme-name"
          placeholder="Enter theme name"
          class="formbold-form-input"
          required
        />
      </div>

      <div class="mb-6 pt-4">
        <label class="formbold-form-label formbold-form-label-2">
          Upload File
        </label>

        <div class="formbold-mb-5 formbold-file-input">
          <input type="file" name="file" id="file" />
          <label for="file">
            <div>
             <!--  <span class="formbold-drop-file"> Drop files here </span>
              <span class="formbold-or"> Or </span> -->
              <span class="formbold-browse"> Browse </span>
            </div>
          </label>
        </div>

        
      </div>
       <div class="mb-6 pt-4">
        <label class="formbold-form-label formbold-form-label-2">
          Upload Theme Screenshot
        </label>

        <div class="formbold-mb-5 formbold-file-input">
          <input type="file" name="file_screenshot" id="file_screenshot" accept="image/*" />
          <label for="file_screenshot">
            <div>
             <!--  <span class="formbold-drop-file"> Drop files here </span>
              <span class="formbold-or"> Or </span> -->
              <span class="formbold-browse"> Browse </span>
            </div>
          </label>
        </div>

     
      </div>
      <?php if(class_exists('HTMLWP_Plugin')): do_action('htmlwpaddoninput'); endif; ?>
      <div>
        <button class="formbold-btn w-full" id="submit">Upload Files</button>
      </div>
    </form>
    <div class="result"></div>
  </div>
  </div>
</div>

<script type="text/javascript">
  jQuery(document).on('click', '#submit', function(e){
   if (confirm('Note: You should have single header for all html.So multiple header in html not acceptable also your html should have <header> tag for header part and <footer> tag footer part of your html.This plugin will use a single header for all pages and html pages will be created in admin pages also. ')){
    e.preventDefault();
if(jQuery('#theme-name').val()!='' && jQuery(document).find('input[type="file"]#file').val()!='' && jQuery(document).find('input[type="file"]#file_screenshot').val()!=''){
  progress();
    var fd = new FormData();
    var file = jQuery(document).find('input[type="file"]#file');
    var file_screen = jQuery(document).find('input[type="file"]#file_screenshot');
   // var caption = jQuery(this).find('input[name=img_caption]');
    var individual_file = file[0].files[0];
    var individual_file_screenshot = file_screen[0].files[0];
    fd.append("file", individual_file);
    fd.append("file_screenshot", individual_file_screenshot);
    var theme_name = jQuery('#theme-name').val();
    fd.append("theme-name", theme_name);  
    fd.append('action', 'htmlwp_upload_file');  
    fd.append('nonce'  , '<?php echo wp_create_nonce( 'htmlwp_upload_file_action' ); ?>')
     //alert('ff'); 
    if ( jQuery(document).find("input").hasClass("addchk") ) {
   // alert('ff'); 
      jQuery(document).find('.addchk:checked').each(function () {
         //jQuery(this).name;
         fd.append(jQuery(this).attr('name'), 1);  
      });
    }
    jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data: fd,

        contentType: false,
        processData: false,
        beforeSend: function ( xhr ) {
               //Add your image loader here
               jQuery('.result').html('<div class="d-flex flex-column align-items-center justify-content-center"><div class="row"><div class="spinner-grow" role="status"><span class="visually-hidden">Loading...</span></div></div><div class="row"><strong>Creating Template</strong></div></div>');
            },
        success: function(response){

            console.log(JSON.parse(response).error);
            if(JSON.parse(response).error)
            {
              jQuery('.result').html('<div class="alert alert-danger d-flex align-items-center" role="alert"><div>'+JSON.parse(response).message+'</div></div>');
            }
            if(JSON.parse(response).success)
            {
              jQuery('form').trigger("reset");
              jQuery('.result').html('<div class="alert alert-info d-flex align-items-center" role="alert"><div>'+JSON.parse(response).message+'</div></div>');
            }
        }
    });
  }
  else
  {
    alert('Fill all the data');
  }
}else{
   alert("Theme Not Created");
} 
   
});

  function progress()
  {
    
  }
</script>