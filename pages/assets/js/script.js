'use strict';



/**
 * navbar toggle
 */

const navOpenBtn = document.querySelector("[data-nav-open-btn]");
const navbar = document.querySelector("[data-navbar]");
const navCloseBtn = document.querySelector("[data-nav-close-btn]");

const navElems = [navOpenBtn, navCloseBtn];

for (let i = 0; i < navElems.length; i++) {
  navElems[i].addEventListener("click", function () {
    navbar.classList.toggle("active");
  });
}



/**
 * search toggle
 */

const searchContainer = document.querySelector("[data-search-wrapper]");
const searchBtn = document.querySelector("[data-search-btn]");

searchBtn.addEventListener("click", function () {
  searchContainer.classList.toggle("active");
});



/**
 * whishlist & cart toggle
 */

const panelBtns = document.querySelectorAll("[data-panel-btn]");
const sidePanels = document.querySelectorAll("[data-side-panel]");

for (let i = 0; i < panelBtns.length; i++) {
  panelBtns[i].addEventListener("click", function () {

    let clickedElemDataValue = this.dataset.panelBtn;

    for (let i = 0; i < sidePanels.length; i++) {

      if (clickedElemDataValue === sidePanels[i].dataset.sidePanel) {
        sidePanels[i].classList.toggle("active");
      } else {
        sidePanels[i].classList.remove("active");
      }

    }

  });
}



/**
 * header sticky & back to top
 */

const header = document.querySelector("[data-header]");
const backTopBtn = document.querySelector("[data-back-top-btn]");

// 1. Mouse Wheel Immediate Trigger (Hides on the very first wheel tick)
window.addEventListener("wheel", function (e) {
  if (e.deltaY > 0 && header) {
    header.classList.add("active");
  } else if (window.scrollY <= 5 && header) {
    header.classList.remove("active");
  }
}, { passive: true });

// 2. Continuous Scroll Synchronization
let isHeaderScrolling = false;
window.addEventListener("scroll", function () {
  if (!isHeaderScrolling) {
    window.requestAnimationFrame(function () {
      const scrolled = window.scrollY > 5;
      if (header) {
        if (scrolled) {
          header.classList.add("active");
        } else {
          header.classList.remove("active");
        }
      }
      if (backTopBtn) {
        if (window.scrollY >= 50) {
          backTopBtn.classList.add("active");
        } else {
          backTopBtn.classList.remove("active");
        }
      }
      isHeaderScrolling = false;
    });
    isHeaderScrolling = true;
  }
}, { passive: true });



/**
 * product details page
 */

const productDisplay = document.querySelector("[data-product-display]");
const productThumbnails = document.querySelectorAll("[data-product-thumbnail]");

for (let i = 0; i < productThumbnails.length; i++) {
  productThumbnails[i].addEventListener("click", function () {
    productDisplay.src = this.src;
    productDisplay.classList.add("fade-anim");

    setTimeout(function () {
      productDisplay.classList.remove("fade-anim");
    }, 250);

  });
}

/**
 * product filter
 */

const filterBtns = document.querySelectorAll("[data-filter-btn]");
const filterItems = document.querySelectorAll("[data-filter-item]");

for (let i = 0; i < filterBtns.length; i++) {
  filterBtns[i].addEventListener("click", function () {
    for (let j = 0; j < filterBtns.length; j++) {
      filterBtns[j].classList.remove("active");
    }
    this.classList.add("active");

    let filterType = this.dataset.filterBtn;

    for (let k = 0; k < filterItems.length; k++) {
      if (filterType === "all" || filterItems[k].dataset.filterItem === filterType) {
        filterItems[k].style.display = "block";
      } else {
        filterItems[k].style.display = "none";
      }
    }
  });
}

// initialize active filter on page load
if (filterBtns.length > 0) {
  const activeBtn = Array.from(filterBtns).find(btn => btn.classList.contains("active"));
  if (activeBtn) {
    activeBtn.click();
  }
}