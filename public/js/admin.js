$( document ).ready(function() {
    Popup.init();
});
// if (screen.width < 980) {
    $('td').bind('click', function (e) {
        if($(e.currentTarget).attr('class') == 'purpose-column' || $(this).parent().attr('data') == 'houses' ) {

        } else {
            var $this = $(this), $parentTR = $this.closest('tr'), id = $this.parent().attr('id');
            var route = $this.parent().attr('data');
            var buttonsObj = "<tr class='row-record-edit'><td colspan='3'><a href='/admin/" + route + "/edit/"+ id +"' class='btn btn-primary'>Редактировать</a></td>" +
                "<td><a href='#' class='remove-record-popup btn btn-danger'>Удалить</a></td>" +
                "<td><a class='btn  btn-outline-primary close-toolbar' href=\"#\">&times;</a></td></tr>";
            if($this.parent().parent().find('.row-record-edit').length >= 1) {
                $this.parent().parent().find('.row-record-edit').remove()
                $(buttonsObj).insertAfter($parentTR);
            } else {
                $(buttonsObj).insertAfter($parentTR);
            }


            // $parentTR.append("<tr><td>hello world</td></tr>")
            // $(e.currentTarget).parent().append("<tr><td>hello world</td></tr>")
        }
        // console.log($(e.currentTarget).attr('class'));
    })
    // document.write('<script type="text/javascript" src="mobile.js"></script>');
// }
$('body').on('click', '.close-toolbar', function (e) {
    e.preventDefault();
    var $this = $(this);
        console.log($this.parent().parent());
    $this.parent().parent().remove()
})
    .on('click', '.remove-record-popup', function (e) {
        e.preventDefault();
        Popup.open('Вы действительно хотите удалить запись?<br><button class="remove-record btn btn-danger">Удалить</button>' , 'warn -message');
    })
    .on('click', '.remove-record', function (e) {

    })


var Popup = {
    init: function () {
        this.closed()
    },
    open: function (msg, classMsg, parent) {
        parent = parent || '';
        classMsg = classMsg || ''
        if (parent) {
            parent.append('<div class="popup"><div class="' + classMsg + '">' + msg + '</div></div>');
            $('.popup').append('<div class="close-order">&times;</div>');
            parent.append('<div class="overlay"></div>');
        } else {
            $('body').append('<div class="popup"><div class="' + classMsg + '">' + msg + '</div></div>');
            $('.popup').append('<div class="close-order">&times;</div>');
            $('body').append('<div class="overlay"></div>');
        }
        $('.popup').css({
            position: 'absolute',
            left: ($(window).width() - $('.popup').outerWidth()) / 2,
            top: ($(window).height() - $('.popup').outerHeight()) / 2 + $(document).scrollTop()
        });
    },
    closed: function (e) {
        e = e || null;
        var elemClass = '.' + $(e).attr('class');
        $('body').on('click', '.overlay, .close-order, .remove-record', function () {
            $('.popup, .overlay').remove();
        });
        $('body').on('keydown', '.popup', (function(eventObject){
            if (eventObject.which == 27) {
                $(this).remove();
                $('.overlay').remove();
            }
        }));
    }
};