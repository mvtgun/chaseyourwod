$( document ).ready(function() {

    var i = 0;
    var poleIdWods = new Array();

    $(".sledovane-ids .sledovane-id").each(function(){
        poleIdWods[i] = $(this).text();
        i++;
    });

    var itemID = $(".idOfWod");
    var itemBtn = $(".btn-success");

    $("#snippet-sledovaneWodsGrid-dataGrid .data-grid tbody tr").each(function(){
            var foundedID = $(this).find(itemID).text();

        for(var i = 0; i<poleIdWods.length;i++){
           var idFromArray = poleIdWods[i].slice(0,-1);
           var idFromArrayTag = poleIdWods[i].substr(poleIdWods[i].length - 1); // => "1"
            if(idFromArrayTag=="A"&&foundedID==idFromArray){
                $(this).find(itemBtn).addClass("active");
            }
        }
        });


        $( ".star-2" )
            .mouseenter(function() {
                    $(".star-1").addClass("rating-active");
            })
            .mouseleave(function() {
                    $(".star-1").removeClass("rating-active");
            });

        $( ".star-3" )
            .mouseenter(function() {
                    $(".star-1").addClass("rating-active");
                    $(".star-2").addClass("rating-active");
            })
            .mouseleave(function() {
                    $(".star-1").removeClass("rating-active");
                    $(".star-2").removeClass("rating-active");
            });

        $( ".star-4" )
            .mouseenter(function() {
                    $(".star-1").addClass("rating-active");
                    $(".star-2").addClass("rating-active");
                    $(".star-3").addClass("rating-active");
            })
            .mouseleave(function() {
                    $(".star-1").removeClass("rating-active");
                    $(".star-2").removeClass("rating-active");
                    $(".star-3").removeClass("rating-active");
            });

        $( ".star-5" )
            .mouseenter(function() {
                $(".star-1").addClass("rating-active");
                $(".star-2").addClass("rating-active");
                $(".star-3").addClass("rating-active");
                $(".star-4").addClass("rating-active");
            })
            .mouseleave(function() {
                    $(".star-1").removeClass("rating-active");
                    $(".star-2").removeClass("rating-active");
                    $(".star-3").removeClass("rating-active");
                    $(".star-4").removeClass("rating-active");
         });


                $('.datepicker').datetimepicker(
                    {
                            locale: 'cs',  // en
                            format: 'DD.MM.YYYY'  // MM/DD/YYYY
                    });

                $('.datetimepicker').datetimepicker(
                    {
                            locale: 'cs',  // en
                            format: 'HH:mm'  // MM/DD/YYYY H:mm
                    });


        tinymce.init({
                //  mode: "specific_textareas",
                //  editor_selector: ".mceEditor",
                selector: '.mceEditor2',
                height: 500,
                plugins: [
                        'advlist autolink lists link image charmap print preview anchor',
                        'searchreplace visualblocks code fullscreen',
                        'insertdatetime media table contextmenu paste code'
                ],
                toolbar: 'insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
                content_css: [
                        'https://fast.fonts.net/cssapi/e6dc9b99-64fe-4292-ad98-6974f93cd2a2.css',
                        'https://www.tinymce.com/css/codepen.min.css'
                ],
                language_url : 'http://www.chaseyourwod.com/js/cs_CZ.js'  // site absolute URL
        });

        tinymce.init({
             //  mode: "specific_textareas",
             //  editor_selector: ".mceEditor",
               selector: '.mceEditor',
               height: 200,
                plugins: [
                        'advlist autolink lists link image charmap print preview anchor',
                        'searchreplace visualblocks code fullscreen',
                        'insertdatetime media table contextmenu paste code'
                ],
                    toolbar: 'insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
                content_css: [
                    'https://fast.fonts.net/cssapi/e6dc9b99-64fe-4292-ad98-6974f93cd2a2.css',
                    'https://www.tinymce.com/css/codepen.min.css'
                ],
                language_url : 'http://www.chaseyourwod.com/js/cs_CZ.js'  // site absolute URL
        });


        var firstRowSelectors =  $('#frm-filterForm').children('fieldset').first().children('.form-group').first();

        firstRowSelectors.find(".checkbox") .slice(8).hide();

        $('#showAllRegions').appendTo(firstRowSelectors);

        $('#showAllRegions').click(function(){

                if(firstRowSelectors.find(".checkbox").is(":hidden")){
                        firstRowSelectors.find(".checkbox").each(function() {
                                $(this).show();
                        });
                        $('#showAllRegions').text("Skrýt kraje");
                }else{
                        firstRowSelectors.find(".checkbox").slice(8).hide();
                        $('#showAllRegions').text("Zobrazit všechny kraje");
                }
        });

        $('#frm-filterForm').children('fieldset').last().find(".col-sm-2").hide();
        $('#frm-filterForm').children('fieldset').last().find(".col-sm-10").removeClass("col-sm-10").addClass("col-sm-12");
        $('#frm-filterForm').children('fieldset').last().find(".form-group").removeClass("col-md-6").addClass("col-md-12");

        $('.listEvents .eventsListFilter #frm-filterForm .checkbox label input').click(function(){
                if($(this).parent().hasClass("rb")){
                        $(this).parent().removeClass("rb");
                }else{
                        $(this).parent().addClass("rb");
                }
        });

        $widthOfWindow = $(window).width();

        if($widthOfWindow>991){
            $('.hp-left').height($('.udalosti-content').innerHeight());
        }

    /*    if($widthOfWindow<991){
            var sirka = $widthOfWindow/4;
            var sirkaFinal = $widthOfWindow - sirka;
            alert(sirkaFinal);
          ///  $(".fb_iframe_widget span").width(sirkaFinal);
            $('.fb_iframe_widget span').attr('style', 'width: '+sirkaFinal+' !important');

        }*/

});

$(function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s); js.id = id;
        js.src = "//connect.facebook.net/en_GB/sdk.js#xfbml=1&version=v2.5";
        fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));

$(function(){
        $("#slideshow > div:gt(0)").hide();
        setInterval(function() {
        $('#slideshow > div:first')
            .fadeOut(1000)
            .next()
            .fadeIn(1000)
            .end()
            .appendTo('#slideshow');
        },  3000);
});

(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
        (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

ga('create', 'UA-78296385-1', 'auto');
ga('send', 'pageview');



