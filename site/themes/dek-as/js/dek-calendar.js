function tableView() {
  let actionsList = document.querySelector("#js-actions-list");
  if (actionsList.classList.contains("actions-list-grid")) {
    actionsList.classList.remove("actions-list-grid");
    actionsList.classList.remove("uk-child-width-1-4@m");
    actionsList.classList.remove("uk-child-width-1-2@s");
    actionsList.classList.remove("uk-grid");
    actionsList.classList.remove("uk-grid-small");
    actionsList.classList.remove("uk-grid-match");

    actionsList.classList.add("actions-list-row");
    actionsList.classList.add("uk-flex");
    actionsList.classList.add("uk-flex-column");
  }
}
function gridView() {
  let actionsList = document.querySelector("#js-actions-list");
  if (actionsList.classList.contains("actions-list-row")) {
    actionsList.classList.remove("actions-list-row");
    actionsList.classList.remove("uk-flex");
    actionsList.classList.remove("uk-flex-column");

    actionsList.classList.add("actions-list-grid");
    actionsList.classList.add("uk-child-width-1-4@m");
    actionsList.classList.add("uk-child-width-1-2@s");
    actionsList.classList.add("uk-grid");
    actionsList.classList.add("uk-grid-small");
    actionsList.classList.add("uk-grid-match");
  }
}



document.querySelectorAll(".calendar__image").forEach((item) => {
  item.addEventListener("mouseover", (event) => {
    item.parentNode.querySelector(".calendar__description").style.display = "block";
    item.parentNode.querySelector(".calendar__image").style.display = "none";
  });
});

document.querySelectorAll(".calendar__description").forEach((item) => {
  item.addEventListener("mouseout", (event) => {
    item.parentNode.querySelector(".calendar__description").style.display = "none";
    item.parentNode.querySelector(".calendar__image").style.display = "block";
  });
});

//pri otvoreni stranky nacitat data podla filtrov v url
getDataAndReload(window.location.href.split("?")[1]);
rewriteFilters();

function clearFilters() {
  window.location.href = window.location.href.split("?")[0];
}



//ak kliknem na span.carr-sellector tak zobrazi sa alebo skyje list, vsetko musi byt pod js-typakce
document.querySelectorAll("#js-typakce .carr-sellector").forEach((item) => {
  item.addEventListener("click", (event) => {
    item.parentNode.parentNode.querySelector(".list").toggleAttribute("hidden");
    gapo(item.parentNode.parentNode.querySelector(".list"));
  });
});
document
  .querySelectorAll("#js-typakce .carr-sellector--name")
  .forEach((item) => {
    item.addEventListener("click", (event) => {
      item.parentNode.parentNode.parentNode
        .querySelector(".list")
        .toggleAttribute("hidden");
    });
  });

//ak list je zobrazeny tak ho skry ak kliknem mimo
document.addEventListener("click", function (event) {
  if (
    !event.target.matches("#js-typakce .carr-sellector") &&
    !event.target.matches("#js-typakce .carr-sellector--name") &&
    !event.target.matches("#js-typakce .null-value")
  ) {
    document.querySelectorAll("#js-typakce .list").forEach((item) => {
      item.setAttribute("hidden", true);
    });
  }
});

document.querySelectorAll("#js-filterdate .carr-sellector").forEach((item) => {
  item.addEventListener("click", (event) => {
    item.parentNode.parentNode.querySelector(".list").toggleAttribute("hidden");
  });
});

document
  .querySelectorAll("#js-filterdate .carr-sellector--name")
  .forEach((item) => {
    item.addEventListener("click", (event) => {
      item.parentNode.parentNode.parentNode
        .querySelector(".list")
        .toggleAttribute("hidden");
    });
  });

//ak list je zobrazeny tak ho skry ak kliknem mimo
document.addEventListener("click", function (event) {
  if (
    !event.target.matches("#js-filterdate .carr-sellector") &&
    !event.target.matches("#js-filterdate .carr-sellector--name") &&
    !event.target.matches("#js-filterdate .null-value")
  ) {
    document.querySelectorAll("#js-filterdate .list").forEach((item) => {
      item.setAttribute("hidden", true);
    });
  }
});

document.querySelectorAll("#js-sectors .carr-sellector").forEach((item) => {
  item.addEventListener("click", (event) => {
    item.parentNode.parentNode.querySelector(".list").toggleAttribute("hidden");
    rewriteFilters();
  });
});
document
  .querySelectorAll("#js-sectors .carr-sellector--name")
  .forEach((item) => {
    item.addEventListener("click", (event) => {
      item.parentNode.parentNode.parentNode
        .querySelector(".list")
        .toggleAttribute("hidden");
      rewriteFilters();
    });
  });

//ak list je zobrazeny tak ho skry ak kliknem mimo
document.addEventListener("click", function (event) {
  if (
    !event.target.matches("#js-sectors .carr-sellector") &&
    !event.target.matches("#js-sectors .carr-sellector--name") &&
    !event.target.matches("#js-sectors .null-value")
  ) {
    document.querySelectorAll("#js-sectors .list").forEach((item) => {
      item.setAttribute("hidden", true);
      rewriteFilters();
    });
  }
});

document.querySelectorAll("#js-online .carr-sellector").forEach((item) => {
  item.addEventListener("click", (event) => {
    item.parentNode.parentNode.querySelector(".list").toggleAttribute("hidden");
  });
});

document
  .querySelectorAll("#js-online .carr-sellector--name")
  .forEach((item) => {
    item.addEventListener("click", (event) => {
      item.parentNode.parentNode.parentNode
        .querySelector(".list")
        .toggleAttribute("hidden");
    });
  });

//ak list je zobrazeny tak ho skry ak kliknem mimo
document.addEventListener("click", function (event) {
  if (
    !event.target.matches("#js-online .carr-sellector") &&
    !event.target.matches("#js-online .carr-sellector--name") &&
    !event.target.matches("#js-online .null-value")
  ) {
    document.querySelectorAll("#js-online .list").forEach((item) => {
      item.setAttribute("hidden", true);
    });
  }
});



const filters = ["sector", "online", "type", "date", "sort", "layout"];
if (filters.some(filter => window.location.href.indexOf(filter) > -1)) {
  rewriteFilters();
}


//ak je v url parameter layout nastaveny na table tak pustim funkciu changeView
if (window.location.href.indexOf("layout") > -1) {
  chaneViewOfLayout();
}

function chaneViewOfLayout() {
  const queryString = window.location.search;
  const urlParams = new URLSearchParams(queryString);
  if (urlParams.get("layout") == "table") {
    tableView();
  } else {
    gridView();
  }
}


document.querySelectorAll("#js-layout .carr-sellector").forEach((item) => {
  item.addEventListener("click", (event) => {
    item.parentNode.parentNode.querySelector(".list").toggleAttribute("hidden");
  });
});
document
  .querySelectorAll("#js-layout .carr-sellector--list")
  .forEach((item) => {
    item.addEventListener("click", (event) => {
      item.parentNode.parentNode.parentNode
        .querySelector(".list")
        .toggleAttribute("hidden");
    });
  });
document.addEventListener("click", function (event) {
  if (
    !event.target.matches("#js-layout .carr-sellector") &&
    !event.target.matches("#js-layout .carr-sellector--name") &&
    !event.target.matches("#js-layout .null-value")
  ) {
    document.querySelectorAll("#js-layout .list").forEach((item) => {
      item.setAttribute("hidden", true);
    });
  }
});



document.querySelectorAll("#js-sort .carr-sellector").forEach((item) => {
  item.addEventListener("click", (event) => {
    item.parentNode.parentNode.querySelector(".list").toggleAttribute("hidden");
  });
});

document
  .querySelectorAll("#js-sort .carr-sellector--list")
  .forEach((item) => {
    item.addEventListener("click", (event) => {
      item.parentNode.parentNode
        .querySelector(".list")
        .toggleAttribute("hidden");
    });
});

document.addEventListener("click", function (event) {
  if (
    !event.target.matches("#js-sort .carr-sellector") &&
    !event.target.matches("#js-sort .carr-sellector--name") &&
    !event.target.matches("#js-sort .null-value")
  ) {
    document.querySelectorAll("#js-sort .list").forEach((item) => {
      item.setAttribute("hidden", true);
    });
  }
});

$('.dek-action').on('click',function()
{
  console.log('click');
    if(document.querySelector('#js-actions-list').classList.contains('actions-list-row')){
        var notThis = $('.dek-action').not($(this));
        notThis.find('.calendar__description').slideUp();
        notThis.removeClass('scrolled');
        $(this).toggleClass('scrolled');
        $(this).find('.calendar__description').slideToggle();
    }
});

function gapo(item){
  //zisti poziciu elementu a nastav jeho poziciu na stred okna horizontalne
  var position = item.offset().left;
  var windowWidth = $(window).width();
  var itemWidth = item.width();
  var itemHeight = item.height();
  var itemPosition = position + itemWidth/2;
  var windowPosition = windowWidth/2;
  var gap = windowPosition - itemPosition;
  item.css('transform','translateX('+gap+'px)');
}


document.querySelectorAll("#js-companies .carr-sellector").forEach((item) => {
  item.addEventListener("click", (event) => {
    item.parentNode.parentNode.querySelector(".list").toggleAttribute("hidden");
  });
});

document
  .querySelectorAll("#js-companies .carr-sellector--name")
  .forEach((item) => {
    item.addEventListener("click", (event) => {
      item.parentNode.parentNode.parentNode
        .querySelector(".list")
        .toggleAttribute("hidden");
    });
  });

//ak list je zobrazeny tak ho skry ak kliknem mimo
document.addEventListener("click", function (event) {
  if (
    !event.target.matches("#js-companies .carr-sellector") &&
    !event.target.matches("#js-companies .carr-sellector--name") &&
    !event.target.matches("#js-companies .null-value")
  ) {
    document.querySelectorAll("#js-companies .list").forEach((item) => {
      item.setAttribute("hidden", true);
    });
  }
});