const barChart = {
    init(data, container) {
        let count = 0;
        let max = 0;
        let maxStr = "";
        $(data).find('.bar').each(function () {
            
            const value = $(this).children('.axis-y').val();
            count++;
            max = (parseInt(max) < parseInt(value)) ? value : max;

            maxStr = max.toString();
            maxStr = maxStr.replace(".", ",");
        });
        this.diagram(data, container, max, count, maxStr);
    },
    diagram(data, container, max, count, maxStr) {
        const activated = [];
        const card = $(`#${container}`).parent('.diagram-content').parent('.diagram-card');
        let originX = 7.5;
        const originY = 85;
        const barWidth = 5;
        const barMargin = (100 - (count * barWidth)) / (count);

        const heading = $(data).find('.heading').val();
        const comment = $(data).find('.comment').val();
        const r = Raphael(container);

        r.setViewBox(0, 0, 400, 250, false);
        r.setSize('100%', '100%');
        card.children('.diagram-title').children('.numeric-record').text(maxStr.toLocaleString());
        card.children('.diagram-title').find('.diagram-comment').find('.heading').text(heading);
        card.children('.diagram-title').find('.diagram-comment').find('.sub').text(comment);
        $(data).find('.bar').each(function (i) {
            const id = activated.push(false) - 1;
            const t = $(this);
            const axisX = t.find('.axis-x').val();
            const axisY = t.find('.axis-y').val();
            const height = ((80) / max) * axisY;
            const z = r.rect(`${originX}%`, card.height() * (originY / 100), `${barWidth}%`, 0).attr({
                'fill': '#E30013',
                'stroke': '#E30013',
                'stroke-width': 2
            });
            r.text(`${2.5 + originX}%`, '98%', axisX).attr({
                font: '19px Arial'
            }).toFront();
            originX = (originX + barWidth + barMargin);
            $(document).scroll(function () {
                const top = $(`#${container}`).offset().top - 700;
                if (top <= $(this).scrollTop() && !activated[id]) {
                    activated[id] = true;
                    animate(z, height, originY);
                }
            });
            z.mouseover(function (e) {
                let hodnotaStlpca = axisY.toString();
                hodnotaStlpca = hodnotaStlpca.replace(".", ",")
                $('.diagram-tooltip').text(hodnotaStlpca);
                $('.diagram-tooltip').show();
                this.animate({
                    fill: '#BC0000'
                }, 1000, 'elastic');
            }).mousemove((e) => {
                $('.diagram-tooltip').css({
                    'top': e.pageY - 50,
                    'left': e.pageX - 25
                });
            }).mouseout(function () {
                this.animate({
                    fill: '#E30013'
                }, 1000, 'elastic');
                $('.diagram-tooltip').hide();
            });
        });
    }
};
function animate(element, height, originY) {
    const step = height / 33;
    let now = element.attr('height');
    now = (typeof (now) === 'string') ? parseInt(now.slice(0, -1)) : now;
    if (now + step < height) {
        element.attr('height', `${now + step}%`);
        element.attr('y', `${originY - (now+step)}%`);
        setTimeout(() => {
            animate(element, height, originY);
        }, 33);
    } else {
        element.attr('height', `${height}%`);
        element.attr('y', `${originY-height}%`);
    }
}