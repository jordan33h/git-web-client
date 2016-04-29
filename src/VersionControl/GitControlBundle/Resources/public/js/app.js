

$(function(){
    
    $('body').on('click','#commit-select-all',function(){
        $('.commit-file').prop( "checked", true );
    });

    $('body').on('click','#commit-deselect-all',function(){
        $('.commit-file').prop( "checked", false );
    });
    
    $('form').submit(function(e){
        if( $(this).hasClass('form-submitted') ){
          e.preventDefault();
          return;
        }
        $(this).addClass('form-submitted');
        
        var $submitButton = $('button.submit');
        if($submitButton.length > 0){
            $submitButton.each(function(){
                var loadingText = $(this).data('loading-text');
                if(!loadingText){
                    loadingText  = 'Loading...';
                }
                $(this).text(loadingText)//.attr("disabled", true);
            });
        }
    });
    
    
    
    $("#versioncontrol_gitcontrolbundle_issuelabel_hexColor").pickAColor({
            showSpectrum            : true,
            showSavedColors         : true,
            saveColorsPerElement    : false,
            fadeMenuToggle          : true,
            showHexInput            : true,
            showBasicColors         : true,
            allowBlank              : false,
            inlineDropdown          : true
      });
      
      /**
       * Form Controls
       */
      
    $('.curriculum').each(function(index ){ 
        var $collectionHolder = $(this);
        $collectionHolder.data('index',$collectionHolder.find('.box').length);
        
        var $addTagLink = $('<a href="#" class="add_tag_link btn btn-link btn-small" id="link-'+index+'"><i class="fa fa-add"></i>Add a new environment</span></a>');               
        var $newLink = $('<div class="add_new_wrapper"></div>').append($addTagLink);
        
        $collectionHolder.append($newLink);
        
        $addTagLink.on('click', function(e) {
            // prevent the link from creating a "#" on the URL
            e.preventDefault();

            addCurriculumForm($collectionHolder,$newLink);
        });
    });
   

    function addCurriculumForm($collectionHolder,$newLink) {
        // Get the data-prototype explained earlier
        var prototype = $collectionHolder.data('prototype');

        // get the new index
        var index = $collectionHolder.data('index');

        // Replace '__name__' in the prototype's HTML to
        // instead be a number based on how many items we have
        var newForm = prototype.replace(/__name__/g, index);

        // increase the index with one for the next item
        $collectionHolder.data('index', index + 1);

        // Display the form in the page in an li, before the "Add a tag" link li
        var $newFormLi = $('<div class="box curriculumbox" data-index="'+index+'"></div>').append(newForm);
        //$collectionHolder.append($newFormLi);
        $newLink.before($newFormLi);
        
    }
    
    /**
     * Delete Button for collections
     */
    $('body').on('click','.remove',function(event){
        event.preventDefault();
        $(this).closest('.box').remove();
    });
    
    
     $('.search-panel .dropdown-menu').find('a').click(function(e) {
        e.preventDefault();
        var param = $(this).attr("href").replace("#","");
        var concept = $(this).text();
        $('#search_concept').text(concept);
        $('#filter').val(param);
    });
    
    
    /**
     * Used on the Remote Brnaches page.
     * Sets the correct values in the New Branch form when the 
     * checkout button is clicked
     */
    $(".checkout-remote").on('click',function(){
        var button = $(this)
            ,remoteName = button.data('remotename')
            ,localName = button.data('localname');
            if(localName){
                localName = localName.split('/').pop();
            }
            $('#form_name').val(localName);
            $('#form_remotename').val(remoteName);
            $('#remote-branch-label').html(remoteName);
    });

   /**
     * Confirm Delete
     */
    $('body').on('click','a[data-confirm]',function(ev) {
            ev.preventDafault();
            var href = $(this).attr('href')
            ,confirmHeader = $(this).data('confirm-header')?$(this).data('confirm-header'):'Confirm Action?';

            if (!$('#dataConfirmModal').length) {
                    $('body').append('<div id="dataConfirmModal" class="modal" role="dialog" aria-labelledby="dataConfirmLabel" aria-hidden="true">\n\
                        <div class="modal-dialog" role="document">\n\
                            <div class="modal-content">\n\
                                <div class="modal-header">\n\
                                    <h3 id="dataConfirmLabel">'+confirmHeader+'</h3>\n\
                                </div>\n\
                                <div class="modal-body"></div>\n\
                                <div class="modal-footer">\n\
                                    <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>\n\
                                    <a class="btn btn-primary" id="dataConfirmOK">OK</a>\n\
                                </div>\n\
                            </div>\n\
                        </div></div>');
            } 
            $('#dataConfirmModal').find('.modal-body').text($(this).attr('data-confirm'));
            $('#dataConfirmOK').attr('href', href);
            $('#dataConfirmModal').modal({show:true});
            return false;
	});
        
        
    /**
     * Permissions Checkboxes
     */
    var $checkboxes = $('.permissions')
    ,$checkboxesOwner = $('.permissions.owner')
    ,$checkboxesGroup = $('.permissions.group')
    ,$checkboxesOther = $('.permissions.other')
    ,$checkboxesSticky = $('.permissions.sticky')
    ,$octal = $('#octal')
    ,$sticky = $('#versioncontrol_gitcontrolbundle_project_projectEnvironment_0_projectEnvironmentFilePerm_permissionSticky')
    ,$owner = $('#versioncontrol_gitcontrolbundle_project_projectEnvironment_0_projectEnvironmentFilePerm_permissionOwner')
    ,$group = $('#versioncontrol_gitcontrolbundle_project_projectEnvironment_0_projectEnvironmentFilePerm_permissionGroup')
    ,$other = $('#versioncontrol_gitcontrolbundle_project_projectEnvironment_0_projectEnvironmentFilePerm_permissionOther');
    
    $checkboxes.change(function() {
        updatePermissions();
    });
    
    function updatePermissions(){
        var ownerVal = 0; 
        $.each($checkboxesOwner, function(){
           if(this.checked) {
               ownerVal +=  parseInt($(this).val());
           }
        });
        $owner.val(ownerVal);
        
        var groupVal = 0; 
        $.each($checkboxesGroup, function(){
           if(this.checked) {
               groupVal += parseInt($(this).val());
           }
        });
        $group.val(groupVal);
        
        var otherVal = 0; 
        $.each($checkboxesOther, function(){
           if(this.checked) {
               otherVal += parseInt($(this).val());
           }
        });
        $other.val(otherVal);
        
        var stickyVal = 0; 
        $.each($checkboxesSticky, function(){
           if(this.checked) {
               stickyVal += parseInt($(this).val());
           }
        });
        $sticky.val(stickyVal);
        
        $octal.val(stickyVal+''+ownerVal+''+groupVal+''+otherVal);
    }
    
    function initPermissions(){
        var ownerVal = $owner.val();
        ownerVal
    }
    
    $("#issueModal").on("show.bs.modal", function(e) {
        var link = $(e.relatedTarget);
        $(this).find(".modal-content").load(link.attr("href"));
    });

    /* SideBar Events: Load content only when side bar opens */
    $(".control-sidebar").on('sidebar.opened',function(){
        var issueData =  $('#control-sidebar-issue-tab').data();
        if(issueData.loaded == false){
             $('#control-sidebar-issue-tab').load(issueData.url);
             $('#control-sidebar-issue-tab').data('loaded',true);
        }
        
        //alert('open');
    });
    
    //Open Default page
    $('.content-wrapper').load( defaultPage,function(){});
});