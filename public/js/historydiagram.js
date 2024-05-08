function setPositions() {
    let odsadenie = 120;
    let poslednyLavy = null;
    let poslednyPravy = null;
    let poslednyNaRovnakejStrane = null;

    const vsetkyRoky = document.getElementById("history").querySelector(".tree").getElementsByClassName("row");
    const sirkaBloku = document.getElementById("history").querySelector(".tree").querySelector('.column').offsetWidth;
    odsadenie = $(this).width() < 770 ? odsadenie : sirkaBloku / 6;

    /**
     * prechadzam vsetky bloky rokov
     */
    for (let i = 0; i < vsetkyRoky.length - 1; i++) {
        /**
         * nulty blok preskakujem pretoze tam netreba ziadne odsadenie
         * posledny blok preskakujem pretoze by posledny bod na stredovej linke nebol na jej konci kedze linka konci na predposlednom bloku
         */

        if (i > 0) {
            /**
             * ak uz existuje na rovnakej strane rok tak pocitam margin-top od neho
             */
            poslednyNaRovnakejStrane = vsetkyRoky[i].getAttribute("direction") == "left" ? poslednyLavy : poslednyPravy;
            if (poslednyNaRovnakejStrane !== null) {
                nastavOdsadenieZHora(odsadenie, poslednyNaRovnakejStrane, vsetkyRoky[i], vsetkyRoky[i - 1]);
            }
            /**
             * v opacnom pripade pocitam od predposledneho
             */
            else {
                nastavitMarginTop = -1 * (vsetkyRoky[i - 1].offsetHeight - odsadenie);
                if (nastavitMarginTop < (-1 * vsetkyRoky[i].offsetHeight)) {
                    nastavitMarginTop = -1 * vsetkyRoky[i].offsetHeight + 20;
                    vsetkyRoky[i].style.marginTop = `${nastavitMarginTop}px`;
                } else {
                    vsetkyRoky[i].style.marginTop = `${nastavitMarginTop}px`;
                }

            }
        }

        /**
         * nastavujem si vzdy posledny blok na danej strane historie
         */
        if (vsetkyRoky[i].getAttribute("direction") == "left") {
            poslednyLavy = vsetkyRoky[i];
        } else {
            poslednyPravy = vsetkyRoky[i];
        }
    }
}

function nastavOdsadenieZHora(odsadenie, poslednyNaRovnakejStrane, aktualny, predposledny) {
    let nastavitMarginTop = 0;
    /**
     * posuvam sa postupne hore a zistujem ci sa neprekryva aktualny s poslednym blokom na rovnakej strane
     * nasledne nastavim margin-top na nejake mnou chcenu hodnotu
     */
    while (doElsCollide(poslednyNaRovnakejStrane, aktualny) === false) {
        nastavitMarginTop -= 10;
        aktualny.style.marginTop = `${nastavitMarginTop}px`;
    }
    nastavitMarginTop += odsadenie;

    /**
     * ak je ale margin-top vacsi ako vyska jeho predchodcu tak by to sposobilo ze sa nizsi rok dostane vyssie
     * tomu zabranim tak ze margin-top nastavim ako vysku posledneho bloku minus mnou chcene odsadenie
     */
    if (predposledny.offsetHeight + nastavitMarginTop < odsadenie || predposledny.offsetHeight < nastavitMarginTop) {
        nastavitMarginTop = -1 * (predposledny.offsetHeight - odsadenie);
    }
    /**
     * ak by nahodou vyslo odsadenie na kladnu hodnotu tak by v stredovej linke bola medzera
     * preto vynulujem margin-top
     */
    if (nastavitMarginTop > 0) {
        nastavitMarginTop = 0;
    }

    aktualny.style.marginTop = `${nastavitMarginTop}px`;
}

/**
 * zistuje ci sa dva bloky na stranke prekryvaju
 * ak ano vrati true
 * ak nie vrati false
 */
function doElsCollide(el1, el2) {
    el1.offsetBottom = el1.offsetTop + el1.offsetHeight;
    el2.offsetBottom = el2.offsetTop + el2.offsetHeight;
    return !(
        (el1.offsetBottom < el2.offsetTop) || (el1.offsetTop > el2.offsetBottom)
    )
};

/**
 * kazdu 0.5s zistuje sirku stranky a spusta prepocitavanie pozicii
 */
const intervalId = window.setInterval(() => {
    const window = $(this);
    if (window.width() > 680) {
        setPositions();
    } else {
        $('.branch').css('margin-top', 0);
    }
}, 500);

/**
 * zavolam pri nacitani stranky
 */
setPositions();