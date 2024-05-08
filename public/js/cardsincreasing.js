var cardsFilled = false;

$(document).scroll(function () {
    if (!cardsFilled)
    {
        $('.card > h2').text(0);
        if ($(this).scrollTop() >= 300)
        {
            cardsFilled = true;
            fillCards();
        }
    }
});

function fillCards()
{
    var oneMoreTime = false;
    $('.card > h2').each(function ()
    {
        var result = $(this).data('value');
        var increaseBy = parseInt(result / 30);
        var value = parseFloat($(this).text());
        if (value !== result)
        {
            oneMoreTime = true;
            value = increase(result, increaseBy, value);
            $(this).text(value);
        } else
        {
            value = value.toLocaleString();
            $(this).text(value);
            $(this).data('value', value);
        }
    });
    if (oneMoreTime)
    {
        setTimeout(function () {
            fillCards();
        }, 50);
    }
}
function increase(result, increaseBy, value = 0)
{
    increaseBy = (increaseBy >= 1) ? increaseBy : 1;
    if (value + increaseBy < result)
    {
        return value + increaseBy;
    } else
    {
        return result;
}
}
//$('*').on('click',function(event)
//{
//    var isHovered = $(event.target).is(":hover"); 
//    if(isHovered)
//    {
//        alert("!!!");
//    }
//})