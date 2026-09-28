// ----------- Vùng chức năng -------------
// 🧩 1️⃣ Include HTML Components
function includeHTML(callback) {
  const elements = document.querySelectorAll("[data-include]");
  if (!elements.length) {
    if (callback) callback();
    return;
  }

  let loaded = 0;

  Promise.all([...elements].map(async (el) => {
    const file = el.getAttribute("data-include");
    if (!file) return;

    // Sử dụng versioning thay vì cache-busting bằng Date.now() để tận dụng cache trình duyệt
    const version = "1.0.0"; // Thay đổi version này khi có cập nhật component
    const cacheKey = `comp-${file}-${version}`;
    let html = sessionStorage.getItem(cacheKey);

    if (!html) {
      // Xóa cache cũ của component này nếu có
      Object.keys(sessionStorage).forEach(key => { if (key.startsWith(`comp-${file}`)) sessionStorage.removeItem(key); });
      const res = await fetch(file, { cache: "reload" }); // Tải lại file mới nhất từ server
      html = await res.text();
      sessionStorage.setItem(cacheKey, html);
    }

    el.innerHTML = html;
    if (typeof initResponsive === "function") initResponsive(el);

    if (++loaded === elements.length) {
      document.dispatchEvent(new Event("includesLoaded"));
      if (callback) callback();
    }
  }));
}

// js thêm active
function initToggleSystem(configs = []) {
  if (!window._toggleSystemState) {
    window._toggleSystemState = { docKeys: new Set(), keyKeys: new Set() };
  }
  const state = window._toggleSystemState;

  configs.forEach((cfg, cfgIndex) => {
    if (!cfg || !cfg.trigger) return;

    const activeClass = cfg.activeClass || "active";
    const behavior = cfg.behavior || "toggle";
    const closeOnOutside = !!cfg.closeOnOutside;
    const closeOnEsc = !!cfg.closeOnEsc;
    const overlayCloses = !!cfg.overlayCloses;
    const innerSelector = cfg.innerSelector || null;
    const closeBtnSelector = cfg.closeBtn || null;
    const groupSelector = cfg.groupSelector || null;

    const triggers = Array.from(document.querySelectorAll(cfg.trigger));
    if (!triggers.length) return;

    const targets = cfg.target ? Array.from(document.querySelectorAll(cfg.target)) : [];

    const closeAll = () => {
      targets.forEach(t => t.classList.remove(activeClass));
      triggers.forEach(t => t.classList.remove(activeClass));
    };

    // bind sự kiện click cho từng trigger (chỉ bind 1 lần)
    triggers.forEach((trigger, idx) => {
      if (trigger.dataset._toggleBound === "true") return;
      if (trigger.dataset.toggleIgnore === "true") return;
      trigger.dataset._toggleBound = "true";

      trigger.addEventListener("click", (e) => {
        e.stopPropagation();

        // Tìm target element ứng với trigger (nếu có)
        let targetEl = null;
        if (cfg.target) {
          if (trigger.dataset && trigger.dataset.target) {
            targetEl = document.querySelector(trigger.dataset.target);
          } else {
            targetEl = targets[idx] || targets[0] || null;
          }
        }

        // ---- behavior activate (tab-like) ----
        if (behavior === "activate") {
          if (groupSelector) {
            document.querySelectorAll(groupSelector).forEach(el => el.classList.remove(activeClass));
          } else {
            triggers.forEach(t => t.classList.remove(activeClass));
          }
          trigger.classList.add(activeClass);

          if (targets.length > 0 && targetEl) {
            targets.forEach(t => t.classList.remove(activeClass));
            targetEl.classList.add(activeClass);
          }
        }

        // ---- toggle mode ----
        else {
          if (targetEl) targetEl.classList.toggle(activeClass);
          else trigger.classList.toggle(activeClass);
        }

        // callback onToggle (nếu có)
        if (typeof cfg.onToggle === "function") {
          try { cfg.onToggle(trigger, idx); } catch (err) { /* ignore */ }
        }

        // -> GỌI onActiveChange bất kể có target hay không
        if (typeof cfg.onActiveChange === "function") {
          const isActive = targetEl ? targetEl.classList.contains(activeClass) : trigger.classList.contains(activeClass);
          try { cfg.onActiveChange(isActive, trigger, targetEl, idx); } catch (err) { /* ignore */ }
        }
      });
    });

    // bind nút đóng (nhiều selector)
    if (closeBtnSelector) {
      Array.from(document.querySelectorAll(closeBtnSelector)).forEach(btn => {
        if (btn.dataset._toggleCloseBound === "true") return;
        btn.dataset._toggleCloseBound = "true";
        btn.addEventListener("click", (e) => {
          e.stopPropagation();
          closeAll();
        });
      });
    }

    // click outside để đóng
    if (closeOnOutside) {
      const docKey = `doc_${cfg.trigger}|${cfg.target || ""}|${cfgIndex}`;
      if (!state.docKeys.has(docKey)) {
        state.docKeys.add(docKey);
        document.addEventListener("click", (e) => {
          const currTriggers = Array.from(document.querySelectorAll(cfg.trigger));
          const currTargets = cfg.target ? Array.from(document.querySelectorAll(cfg.target)) : [];

          const clickedOnTrigger = currTriggers.some(t => t.contains(e.target));
          const clickedOnOverlay = overlayCloses && currTargets.some(t => e.target === t);

          const clickedInsideTarget = currTargets.some(t => {
            const inner = innerSelector ? t.querySelector(innerSelector) : t;
            return inner && inner.contains(e.target);
          });

          if (clickedOnOverlay) {
            currTargets.forEach(t => t.classList.remove(activeClass));
            currTriggers.forEach(t => t.classList.remove(activeClass));
            return;
          }

          if (!clickedInsideTarget && !clickedOnTrigger) {
            currTargets.forEach(t => t.classList.remove(activeClass));
            currTriggers.forEach(t => t.classList.remove(activeClass));
          }
        });
      }
    }

    // ESC để đóng
    if (closeOnEsc) {
      const escKey = `esc_${cfg.trigger}|${cfg.target || ""}|${cfgIndex}`;
      if (!state.keyKeys.has(escKey)) {
        state.keyKeys.add(escKey);
        document.addEventListener("keydown", (e) => {
          if (e.key === "Escape") {
            const currTargets = cfg.target ? Array.from(document.querySelectorAll(cfg.target)) : [];
            const currTriggers = Array.from(document.querySelectorAll(cfg.trigger));
            currTargets.forEach(t => t.classList.remove(activeClass));
            currTriggers.forEach(t => t.classList.remove(activeClass));
          }
        });
      }
    }

    // === gọi onActiveChange cho trạng thái ban đầu (nếu có active sẵn trong DOM) ===
    if (typeof cfg.onActiveChange === "function") {
      // delay một tick để đảm bảo các class có sẵn đã gán xong (nếu include động)
      setTimeout(() => {
        Array.from(document.querySelectorAll(cfg.trigger)).forEach((tr, i) => {
          const targetEl = cfg.target ? (document.querySelectorAll(cfg.target)[i] || document.querySelectorAll(cfg.target)[0]) : null;
          const isActive = targetEl ? targetEl.classList.contains(activeClass) : tr.classList.contains(activeClass);
          if (isActive) {
            try { cfg.onActiveChange(true, tr, targetEl, i); } catch (err) { }
          }
        });
      }, 0);
    }
  });
}

// 🖼️ 2️⃣ Lazy Load + Set Dimensions
function applyImageEnhancements(root = document) {
  root.querySelectorAll("img").forEach(img => {
    // Lazy load
    if (!img.hasAttribute("loading")) img.setAttribute("loading", "lazy");

    // Alt text
    if (!img.hasAttribute("alt") || img.alt.trim() === "") {
      const fileName = img.src.split("/").pop().split(".")[0] || "image";
      img.setAttribute("alt", fileName.replace(/[-_]/g, " "));
    }

    // Hàm set kích thước an toàn
    const setDim = () => {
      if (img.naturalWidth > 0 && img.naturalHeight > 0) {
        if (!img.hasAttribute("width")) img.setAttribute("width", img.naturalWidth);
        if (!img.hasAttribute("height")) img.setAttribute("height", img.naturalHeight);
      }
    };

    // Nếu ảnh đã load sẵn (cache hoặc render sớm)
    if (img.complete) setTimeout(setDim, 50);
    else img.addEventListener("load", setDim);

    // Chỉ xử lý khi xuất hiện trong viewport
    const io = new IntersectionObserver((entries, obs) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          setDim();
          obs.unobserve(entry.target);
        }
      });
    }, { rootMargin: "200px 0px" });
    io.observe(img);
  });
}

// ✨ 3️⃣ Scroll Reveal Effect
function initRevealEffect() {
  const sections = document.querySelectorAll("section, footer");
  if (!sections.length) return;

  sections.forEach(sec => sec.classList.add("hidden-section"));

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const el = entry.target;

        el.classList.add("show-up");

        observer.unobserve(el);
      }
    });
  }, {
    threshold: 0,
    rootMargin: "0px 0px -100px 0px"
  });

  sections.forEach(sec => observer.observe(sec));
}

function extractHeadingData(contentSelector, headingTags = "h1, h2, h3, h4, h5, h6") {
  const content = contentSelector === "all" ? document : document.querySelector(contentSelector);

  if (!content) {
    return [];
  }

  const headings = content.querySelectorAll(headingTags);
  if (!headings.length) return [];

  const toSlug = str => str
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/đ/g, "d").replace(/Đ/g, "D")
    .replace(/\s+/g, "-")
    .replace(/[^\w\-]/g, "")
    .toLowerCase();

  const data = [];

  headings.forEach((h, i) => {
    const text = h.textContent.trim();

    let id = h.id || toSlug(text) || `heading-${i}`;

    if (document.getElementById(id) && document.getElementById(id) !== h) {
      let baseId = id;
      let counter = 1;
      while (document.getElementById(`${baseId}-${counter}`) && document.getElementById(`${baseId}-${counter}`) !== h) {
        counter++;
      }
      id = `${baseId}-${counter}`;
    }

    h.id = id;
    data.push({
      id: id,
      text: text,
      tag: h.tagName.toLowerCase()
    });
  });

  return data;
}

// HÀM 2: NHÂN BẢN TEMPLATE VÀ ĐỔ DỮ LIỆU
function renderDynamicList(headingData, targetSelector) {
  if (!headingData || headingData.length === 0) return;

  const targetContainer = document.querySelector(targetSelector);
  if (!targetContainer) {
    console.log('Không tìm thấy menu');
    return;
  }

  const template = targetContainer.firstElementChild;
  if (!template) {
    console.warn(`Vui lòng để lại 1 thẻ con trong ${targetSelector} để làm mẫu!`);
    return;
  }

  targetContainer.innerHTML = "";

  headingData.forEach(item => {
    const clone = template.cloneNode(true);
    const aTag = clone.querySelector("a");

    if (aTag) {
      aTag.href = `#${item.id}`;

      // FIX LỖI 1: Tôn trọng text gốc, gõ sao ra vậy, không ép toLowerCase nữa!
      aTag.textContent = item.text;

      aTag.addEventListener("click", e => {
        e.preventDefault();
        const targetSection = document.getElementById(item.id);

        // Cuộn trang
        if (targetSection) {
          const headerHeight = 100; // Nhớ check lại chiều cao thực tế của menu header bồ nha
          const elementPosition = targetSection.getBoundingClientRect().top;
          const offsetPosition = elementPosition + window.scrollY - headerHeight;

          window.scrollTo({
            top: offsetPosition,
            behavior: "smooth"
          });
        }

        const targetBody = document.querySelector('.table-heading__body');
        if (targetBody) targetBody.classList.remove('active');

        const targetTrigger = document.querySelector('.table-heading__top');
        if (targetTrigger) targetTrigger.classList.remove('active');
      });
    }

    targetContainer.appendChild(clone);
  });
}

// 🧩 2️⃣ Hàm dùng chung cho tất cả Swiper
function initSwiperSlider({
  mainSelector,
  wrapperSelector = null,
  minSlides = 0,
  autoplay = false,
  spaceBetween = 0,
  slidesPerView = 1,
  slidesPerGroup = 1,
  loop = false,
  autoGroupRows = 1,
  navigation = { nextEl: null, prevEl: null },
  pagination = { el: null, clickable: true },
  breakpoints = null,
  ...extraOptions
}) {
  const swiperContainers = document.querySelectorAll(mainSelector);
  if (swiperContainers.length === 0) return;

  let finalOptions = { ...extraOptions };
  let finalBreakpoints = breakpoints ? { ...breakpoints } : null;
  let finalLoop = loop;

  // (Đoạn logic xử lý grid và loop giữ nguyên)
  if (autoGroupRows > 1) {
    delete finalOptions.grid;
    if (finalBreakpoints) {
      Object.keys(finalBreakpoints).forEach(key => {
        delete finalBreakpoints[key].grid;
      });
    }
  } else {
    let hasGrid = finalOptions.grid && finalOptions.grid.rows > 1;
    if (finalBreakpoints && !hasGrid) {
      hasGrid = Object.values(finalBreakpoints).some(bp => bp.grid && bp.grid.rows > 1);
    }
    if (hasGrid && finalLoop) {
      finalLoop = false;
    }
  }

  swiperContainers.forEach(container => {
    const wrapper = container.querySelector('.swiper-wrapper');
    if (!wrapper) return;

    // (Đoạn 1. Gom nhóm và Đoạn 2. Hack Loop giữ nguyên không đổi)
    if (autoGroupRows > 1 && !container.classList.contains('js-grouped')) {
      const originalSlides = Array.from(wrapper.children);
      wrapper.innerHTML = '';
      const fragment = document.createDocumentFragment();
      for (let i = 0; i < originalSlides.length; i += autoGroupRows) {
        const chunk = originalSlides.slice(i, i + autoGroupRows);
        const groupSlide = document.createElement('div');
        groupSlide.className = 'swiper-slide flex flex-col gap-20';
        chunk.forEach(slide => {
          slide.classList.remove('swiper-slide', 'col-3', 'col-4', 'col-2', 'col-6');
          slide.style.width = '100%';
          groupSlide.appendChild(slide);
        });
        fragment.appendChild(groupSlide);
      }
      wrapper.appendChild(fragment);
      container.classList.add('js-grouped');
    }

    if (finalLoop && wrapper && !container.classList.contains('no-clone')) {
      const currentSlides = Array.from(wrapper.children);
      const totalSlides = currentSlides.length;
      let maxView = slidesPerView;
      if (finalBreakpoints) {
        Object.values(finalBreakpoints).forEach(bp => {
          if (bp.slidesPerView > maxView) maxView = bp.slidesPerView;
        });
      }
      const threshold = Math.max(minSlides, maxView * 2);
      if (totalSlides > 0 && totalSlides < threshold) {
        const times = Math.ceil(threshold / totalSlides) - 1;
        const cloneFragment = document.createDocumentFragment();
        for (let i = 0; i < times; i++) {
          currentSlides.forEach(s => cloneFragment.appendChild(s.cloneNode(true)));
        }
        wrapper.appendChild(cloneFragment);
      }
    }

    const scope = wrapperSelector ? container.closest(wrapperSelector) : container.parentElement;
    const nav = navigation && (navigation.nextEl || navigation.prevEl) ? {
      nextEl: scope && navigation.nextEl ? scope.querySelector(navigation.nextEl) : navigation.nextEl,
      prevEl: scope && navigation.prevEl ? scope.querySelector(navigation.prevEl) : navigation.prevEl,
    } : false;

    const pag = pagination && pagination.el ? {
      ...pagination,
      el: scope && pagination.el ? scope.querySelector(pagination.el) : pagination.el,
    } : false;
    let localAutoplay = autoplay;
    if (container.classList.contains('no-auto-slide') || (scope && scope.classList.contains('no-auto-slide'))) {
      localAutoplay = false;
    }
    const swiperOptions = {
      slidesPerView: slidesPerView,
      slidesPerGroup: slidesPerGroup,
      spaceBetween: spaceBetween,
      loop: finalLoop,
      navigation: nav,
      pagination: pag,
      breakpoints: finalBreakpoints,
      autoplay: localAutoplay ? {
        delay: 2500,
        disableOnInteraction: false,
        ...(typeof localAutoplay === 'object' ? localAutoplay : {})
      } : false,
      ...finalOptions
    };

    setTimeout(() => {
      new Swiper(container, swiperOptions);
    }, 0);

  });
}

// js roll to top
function initScrollToTop(btnId = "btnToTop", showOffset = 1000) {
  const scrollBtn = document.getElementById(btnId);
  if (!scrollBtn) return;

  window.addEventListener("scroll", () => {
    if (window.scrollY > showOffset) {
      scrollBtn.classList.add("show");
    } else {
      scrollBtn.classList.remove("show");
    }
  });

  scrollBtn.addEventListener("click", () => {
    window.scroll({
      top: 0,
      behavior: "smooth",
    });
  });
}

// js validate form
function validateField(input) {
  const group = input.closest(".form-group");
  const error = group?.querySelector(".error-msg");
  let message = "";

  const value = input.value.trim();

  if (input.hasAttribute("required") && !value) {
    message = input.dataset.msg || "Vui lòng không để trống";
  }

  if (!message && input.type === "email" && value) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(value)) message = "Email không hợp lệ";
  }

  if (!message && input.hasAttribute("minlength")) {
    const min = +input.getAttribute("minlength");
    if (value.length < min) {
      message = input.dataset.msg || `Tối thiểu ${min} ký tự`;
    }
  }

  if (!message && input.tagName === "SELECT" && input.required) {
    if (!input.value) message = "Vui lòng chọn một giá trị";
  }

  if (!message && input.type === "checkbox" && input.required) {
    if (!input.checked) message = "Vui lòng xác nhận";
  }

  if (!message && input.pattern && input.value) {
    const regex = new RegExp(input.pattern);
    if (!regex.test(input.value)) {
      message = input.dataset.msg || "Giá trị không hợp lệ";
    }
  }

  if (group) group.classList.toggle("error", !!message);
  if (error) error.textContent = message;

  return !message;
}

function validateForm(form) {
  let isValid = true;
  form.querySelectorAll("input, textarea").forEach(input => {
    if (!validateField(input)) isValid = false;
  });
  return isValid;
}

function initFormValidation(root = document) {
  root.querySelectorAll(".js-validate-form").forEach(form => {
    if (form.dataset._validated) return;
    form.dataset._validated = "true";

    form.querySelectorAll("input, textarea").forEach(input => {
      input.addEventListener("input", () => validateField(input));
    });

    form.addEventListener("submit", e => {
      if (!validateForm(form)) e.preventDefault();
    });
  });
}

// js add active định vị ở menu
function initUniversalActiveMenu(menuSelector = '', activeClassName = 'active') {
  const currentUrl = window.location.href.split(/[?#]/)[0];

  const menuLinks = document.querySelectorAll(`${menuSelector} a`);
  let bestMatch = null;
  let longestMatchLength = 0;

  menuLinks.forEach(link => {
    const hrefAttr = link.getAttribute('href');
    if (!hrefAttr || hrefAttr.startsWith('#') || hrefAttr.startsWith('javascript')) return;
    const linkUrl = link.href.split(/[?#]/)[0];

    // ==========================================
    // VŨ KHÍ MỚI: Bắt theo keyword từ data-match
    // ==========================================
    const matchKeyword = link.getAttribute('data-match');
    if (matchKeyword && currentUrl.includes(matchKeyword)) {
      bestMatch = link;
      longestMatchLength = 9999; // Cấp quyền ưu tiên tuyệt đối, khỏi check mấy cái dưới
      return;
    }

    // Logic cũ (Vẫn giữ để chạy cho các trang bình thường không có data-match)
    if (currentUrl === linkUrl) {
      bestMatch = link;
      longestMatchLength = linkUrl.length;
    } else if (currentUrl.startsWith(linkUrl)) {
      const isHomePage = linkUrl.endsWith('/') || linkUrl.endsWith('index.html') || linkUrl.endsWith('/en') || linkUrl.endsWith('/kn');

      if (!isHomePage && linkUrl.length > longestMatchLength) {
        bestMatch = link;
        longestMatchLength = linkUrl.length;
      }
    }
  });

  if (bestMatch) {
    bestMatch.classList.add(activeClassName);
    const parentMenu = bestMatch.closest(menuSelector);
    if (parentMenu) parentMenu.classList.add(activeClassName);
  } else {
    const homeLink = Array.from(menuLinks).find(link => {
      const lUrl = link.href.split(/[?#]/)[0];
      return lUrl.endsWith('/') || lUrl.endsWith('index.html') || lUrl.endsWith('/en') || lUrl.endsWith('/kn');
    });

    if (homeLink) {
      homeLink.classList.add(activeClassName);
      const parentMenu = homeLink.closest(menuSelector);
      if (parentMenu) parentMenu.classList.add(activeClassName);
    }
  }
}

// Hàm tự động quét và gắn hiệu ứng Zoom
function initAutoImageZoom(gallerySelector, zoomScale = 1.5) {
  const galleries = document.querySelectorAll(gallerySelector);
  if (galleries.length === 0) return;

  galleries.forEach(gallery => {
    const items = gallery.querySelectorAll('.product-main__item');

    items.forEach(item => {
      const img = item.querySelector('img');
      const video = item.querySelector('iframe, video');
      if (video) {
        item.classList.add('is-video-item');
        return;
      }

      if (img) {
        item.classList.add('js-zoom-container', 'pos-rel', 'overflow-hidden');

        item.addEventListener('mousemove', function (e) {

          if (gallery.swiper && gallery.swiper.autoplay) {
            gallery.swiper.autoplay.stop();
          }

          const rect = item.getBoundingClientRect();
          const x = ((e.clientX - rect.left) / rect.width) * 100;
          const y = ((e.clientY - rect.top) / rect.height) * 100;

          img.style.transformOrigin = `${x}% ${y}%`;
          img.style.transform = `scale(${zoomScale})`; // Xài biến zoomScale (1.5)
        });

        item.addEventListener('mouseleave', function () {

          img.style.transformOrigin = 'center';
          img.style.transform = 'scale(1)';

          if (gallery.swiper && gallery.swiper.autoplay) {
            gallery.swiper.autoplay.start();
          }
        });
      }
    });
  });
}

// ----------- Vùng gọi biến --------------
document.addEventListener("DOMContentLoaded", () => {
  includeHTML(() => {
    initSwiperSlider({
      mainSelector: '.slide-container',
      minSlides: 3,
      autoplay: { delay: 3000, disableOnInteraction: false },
      loop: true,
      slidesPerView: 1,
      spaceBetween: 0,
      navigation: {
        nextEl: '.slide-container .swiper-button-next',
        prevEl: '.slide-container .swiper-button-prev',
      },
      pagination: {
        el: '.swiper-pagination.custom-dots', // Selector cho dots
        clickable: true,
      },
    });

    initSwiperSlider({
      mainSelector: '.project-swiper',
      slidesPerView: 3,
      spaceBetween: 20,
      loop: true,
      minSlides: 6,
      autoplay: {
        delay: 3000,
      },
      pagination: {
        el: '.custom-dots',
      },
      navigation: {
        nextEl: '.project-slider-wrapper .custom-next-btn',
        prevEl: '.project-slider-wrapper .custom-prev-btn',
      },
      // Thêm Breakpoints responsive
      breakpoints: {
        320: {
          slidesPerView: 2, // Điện thoại hiện 1
          spaceBetween: 10
        },

        500: {
          slidesPerView: 2, // Điện thoại hiện 1
          spaceBetween: 10
        },
        768: {
          slidesPerView: 3, // Tablet hiện 2
          spaceBetween: 15
        },
        1024: {
          slidesPerView: 4, // PC hiện 3
          spaceBetween: 20
        }
      }
    });

    initSwiperSlider({
      mainSelector: '.news-swiper',
      slidesPerView: 3,
      spaceBetween: 20,
      loop: true,
      minSlides: 6,
      autoplay: {
        delay: 3000,
      },
      pagination: {
        el: '.custom-dots',
      },
      navigation: {
        nextEl: '.news-slider-wrapper .custom-next-btn',
        prevEl: '.news-slider-wrapper .custom-prev-btn',
      },
      // Thêm Breakpoints responsive
      breakpoints: {
        320: {
          slidesPerView: 2, // Điện thoại hiện 1
          spaceBetween: 10
        },
        768: {
          slidesPerView: 3, // Tablet hiện 2
          spaceBetween: 15
        },
        1024: {
          slidesPerView: 4, // PC hiện 3
          spaceBetween: 20
        }
      }
    });

    initSwiperSlider({
      mainSelector: '.guest-comment__swiper',
      minSlides: 8,
      // autoplay: { delay: 3000, disableOnInteraction: false },
      loop: true,
      slidesPerView: 1,
      spaceBetween: 20,
      navigation: {
        nextEl: '.guest-comment__wrapper .swiper-button-next',
        prevEl: '.guest-comment__wrapper .swiper-button-prev',
      },
      pagination: {
        el: '.custom-dots',
        clickable: true,
      },
      breakpoints: {
        1200: { slidesPerView: 3, spaceBetween: 20, },
        900: { slidesPerView: 3, spaceBetween: 20, },
        500: { slidesPerView: 2, spaceBetween: 20, },
      },
    });

    initSwiperSlider({
      mainSelector: '.service-list',
      minSlides: 0,
      loop: true,
      autoplay: false,
      slidesPerView: 1,
      grid: {
        rows: 1,
        fill: 'row'
      },
      navigation: {
        nextEl: '.service-slider-wrapper .custom-next-btn',
        prevEl: '.service-slider-wrapper .custom-prev-btn',
      },
      pagination: {
        el: '.service-list .swiper-pagination',
        clickable: true,
      },
      breakpoints: {
        1200: {
          slidesPerView: 3,
          spaceBetween: 20,
          grid: {
            rows: 2,
            fill: 'row'
          },
        },
        1000: {
          slidesPerView: 3,
          spaceBetween: 20,
          grid: {
            rows: 1,
            fill: 'row'
          },
        },
        500: {
          slidesPerView: 2,
          spaceBetween: 20,
          grid: {
            rows: 1,
            fill: 'row'
          },
        },
      },
    });

    initSwiperSlider({
      mainSelector: '.service-intro__list',
      minSlides: 0,
      loop: true,
      slidesPerView: 1,
      spaceBetween: 20,
      grid: {
        rows: 1,
        fill: 'row'
      },
      navigation: {
        nextEl: '.process-slider-wrapper .custom-next-btn',
        prevEl: '.process-slider-wrapper .custom-prev-btn',
      },
      pagination: {
        el: '.service-list .swiper-pagination',
        clickable: true,
      },
      breakpoints: {
        1200: {
          slidesPerView: 3,
          spaceBetween: 20,
          grid: {
            rows: 2,
            fill: 'row'
          },
        },
        1000: {
          slidesPerView: 3,
          spaceBetween: 20,
          grid: {
            rows: 1,
            fill: 'row'
          },
        },
        500: {
          slidesPerView: 2,
          spaceBetween: 20,
          grid: {
            rows: 1,
            fill: 'row'
          },
        },
      },
    });


    initSwiperSlider({
      mainSelector: '.logo-brand__swiper',
      minSlides: 18,
      autoplay: { delay: 3000, disableOnInteraction: false },
      loop: true,
      slidesPerView: 1,
      spaceBetween: 20,
      grid: {
        rows: 2,
        fill: 'row'
      },
      navigation: {
        nextEl: '.logo-brand-wrapper .swiper-button-next',
        prevEl: '.logo-brand-wrapper .swiper-button-prev',
      },
      breakpoints: {
        1200: {
          slidesPerView: 6, slidesPerGroup: 6,
          grid: {
            rows: 3,
            fill: 'row'
          },
        },
        900: {
          slidesPerView: 6, slidesPerGroup: 6,
          grid: {
            rows: 3,
            fill: 'row'
          },
        },
        500: {
          slidesPerView: 6, slidesPerGroup: 6,
          grid: {
            rows: 3,
            fill: 'row'
          },
        },
        200: {
          slidesPerView: 6, slidesPerGroup: 6,
          grid: {
            rows: 3,
            fill: 'row'
          },
        },
      },
    });

    // 1. SLIDER BANNER (Hiện 1 ảnh to, tự động chạy)
    initSwiperSlider({
      mainSelector: '.js-slider-banner', // Tên class dùng chung
      wrapperSelector: '.js-slider-wrapper', // BẮT BUỘC có thẻ bọc ngoài chung class này
      minSlides: 3,
      slidesPerView: 1,
      spaceBetween: 0,
      loop: true,
      // autoplay: { delay: 3000, disableOnInteraction: false },
      navigation: {
        nextEl: '.swiper-button-next', // Tự động tìm nút trong wrapper
        prevEl: '.swiper-button-prev',
      },
      pagination: {
        el: '.swiper-pagination',
        clickable: true,
      },
    });

    // 2. SLIDER 3 CỘT (Dùng cho Dự án, Bình luận, Dịch vụ...)
    initSwiperSlider({
      mainSelector: '.js-slider-3cols',
      wrapperSelector: '.js-slider-wrapper',
      minSlides: 6,
      loop: true,
      // autoplay: { delay: 3000, disableOnInteraction: false },
      navigation: {
        nextEl: '.swiper-button-next', // Tự động tìm nút trong wrapper
        prevEl: '.swiper-button-prev',
      },
      pagination: { el: '.custom-dots', clickable: true },
      breakpoints: {
        320: { slidesPerView: 1, spaceBetween: 10 },
        768: { slidesPerView: 2, spaceBetween: 15 },
        1024: { slidesPerView: 3, spaceBetween: 20 }
      }
    });

    // 3. SLIDER 4 CỘT (Dùng cho Tin tức, Sản phẩm...)
    initSwiperSlider({
      mainSelector: '.js-slider-4cols',
      wrapperSelector: '.js-slider-wrapper',
      minSlides: 6,
      loop: true,
      autoplay: { delay: 3000, disableOnInteraction: false },
      navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
      },
      pagination: {
        el: '.swiper-pagination',
        clickable: true
      },
      breakpoints: {
        320: { slidesPerView: 2, spaceBetween: 10 },
        768: { slidesPerView: 3, spaceBetween: 15 },
        1024: { slidesPerView: 4, spaceBetween: 20 }
      }
    });

    initSwiperSlider({
      mainSelector: '.js-slider-4cols-no-auto',
      wrapperSelector: '.js-slider-wrapper',
      minSlides: 0,
      loop: false,
      navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
      },
      pagination: {
        el: '.swiper-pagination',
        clickable: true
      },
      breakpoints: {
        320: { slidesPerView: 2, spaceBetween: 10 },
        768: { slidesPerView: 3, spaceBetween: 15 },
        1024: { slidesPerView: 4, spaceBetween: 20 }
      }
    });

    initSwiperSlider({
      mainSelector: '.js-slider-5cols',
      wrapperSelector: '.js-slider-wrapper',
      minSlides: 6,
      loop: true,
      autoplay: { delay: 3000, disableOnInteraction: false },
      navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
      },
      pagination: {
        el: '.swiper-pagination', // ĐÃ SỬA CHUẨN
        clickable: true
      },
      breakpoints: {
        320: { slidesPerView: 2, spaceBetween: 10 },
        768: { slidesPerView: 4, spaceBetween: 15 },
        1024: { slidesPerView: 5, spaceBetween: 20 }
      }
    });

    initSwiperSlider({
      mainSelector: '.js-slider-6cols',
      wrapperSelector: '.js-slider-wrapper',
      minSlides: 6,
      loop: true,
      autoplay: { delay: 3000, disableOnInteraction: false },
      navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
      },
      pagination: {
        el: '.swiper-pagination', // ĐÃ SỬA CHUẨN
        clickable: true
      },
      breakpoints: {
        320: { slidesPerView: 2, spaceBetween: 10 },
        768: { slidesPerView: 4, spaceBetween: 15 },
        1024: { slidesPerView: 6, spaceBetween: 20 }
      }
    });

    initSwiperSlider({
      mainSelector: '.js-slider-8cols',
      wrapperSelector: '.js-slider-wrapper',
      minSlides: 8,
      loop: true,
      autoplay: { delay: 3000, disableOnInteraction: false },
      navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
      },
      pagination: {
        el: '.swiper-pagination', // ĐÃ SỬA CHUẨN
        clickable: true
      },
      breakpoints: {
        320: { slidesPerView: 2, spaceBetween: 10 },
        768: { slidesPerView: 4, spaceBetween: 15 },
        1024: { slidesPerView: 6, spaceBetween: 20 }
      }
    });


    // 4. SLIDER LOGO / THƯƠNG HIỆU (Dạng lưới Grid chia hàng)
    initSwiperSlider({
      mainSelector: '.js-slider-logo',
      wrapperSelector: '.js-slider-wrapper',
      minSlides: 18,
      loop: true,
      autoplay: { delay: 3000, disableOnInteraction: false },
      navigation: {
        nextEl: '.swiper-button-next', // Tự động tìm nút trong wrapper
        prevEl: '.swiper-button-prev',
      },
      pagination: { el: '.swiper-pagination', clickable: true },
      breakpoints: {
        500: {
          slidesPerView: 2, spaceBetween: 20,
          grid: { rows: 2, fill: 'row' }
        },
        900: {
          slidesPerView: 4, spaceBetween: 20,
          grid: { rows: 2, fill: 'row' }
        },
        1200: {
          slidesPerView: 5, spaceBetween: 20,
          grid: { rows: 3, fill: 'row' }
        },
      }
    });

    // 5. SLIDER lúc đầu là tỉnh khi xuống mobile thì thành slide

    initSwiperSlider({
      mainSelector: '.js-slider-grid-to-slide',
      wrapperSelector: '.js-slider-wrapper',
      minSlides: 6,

      loop: false,
      autoplay: false,

      navigation: { nextEl: '.custom-next-btn', prevEl: '.custom-prev-btn' },
      pagination: { el: '.custom-dots', clickable: true },

      breakpoints: {
        320: {
          slidesPerView: 2,
          spaceBetween: 10,
          grid: { rows: 1, fill: 'row' }
        },
        768: {
          slidesPerView: 3,
          spaceBetween: 15,
          grid: { rows: 1, fill: 'row' }
        },
        1024: {
          slidesPerView: 3,
          spaceBetween: 20,
          grid: {
            rows: 2,
            fill: 'row'
          }
        }
      }
    });

    // 6.1 Khởi tạo Slider Hình Nhỏ (Thumb) TRƯỚC
    initSwiperSlider({
      mainSelector: '.js-gallery-thumb',
      wrapperSelector: '.js-slider-wrapper',
      slidesPerView: 4,
      spaceBetween: 10,
      loop: true,
      autoplay: true,
      watchSlidesProgress: true, // LƯU Ý TỬ HUYỆT: Bắt buộc phải có dòng này để đồng bộ
      breakpoints: {
        320: { slidesPerView: 3, spaceBetween: 10 },
        768: { slidesPerView: 4, spaceBetween: 15 }
      }
    });

    // 6.2 Khởi tạo Slider Hình To (Main) SAU
    initSwiperSlider({
      mainSelector: '.js-gallery-main',
      wrapperSelector: '.js-slider-wrapper',
      slidesPerView: 1,
      spaceBetween: 0,
      loop: true,
      autoplay: true,// Dạng Gallery thì thường để false cho chuẩn
      navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
      },
      // TÍNH NĂNG ĂN TIỀN: Nối class của thằng nhỏ vào đây
      thumbs: {
        swiper: '.js-gallery-thumb'
      }
    });


    initToggleSystem([
      {
        trigger: ".pagination-btn__custom.page-num",
        behavior: "activate",
        activeClass: "active",
      },
      {
        trigger: ".menu-container__bar",
        target: ".m-menu",
        behavior: "toggle",
        activeClass: "active",
        closeOnOutside: true,
        closeOnEsc: true,
        innerSelector: ".m-menu__link"
      },
      {
        trigger: ".news-detail__content h3",
        behavior: "activate",
        activeClass: "active",
      },
      {
        trigger: ".pagination-btn",
        behavior: "activate",
        activeClass: "active",
      },
      {
        trigger: ".page-btn",
        behavior: "activate",
        activeClass: "active",
      },
      {
        trigger: ".expo-faq__icon",
        target: ".expo-faq__answer",
        behavior: "toggle",
        activeClass: "active",
      },
      {
        trigger: ".service-sidebar__item",
        behavior: "activate",
        activeClass: "active",
      },
      {
        trigger: ".table-heading__top",
        target: " .table-heading__body",
        behavior: "toggle",
        activeClass: "active",
      },
      {
        trigger: ".btn-register__cal",
        target: ".popup-register__container",
        behavior: "toggle",
        activeClass: "active",
        closeOnOutside: true,
        closeOnEsc: true,
        innerSelector: ".contact-section__container"
      },
      {
        trigger: ".btn-write-review",
        target: ".popup-comment__container",
        behavior: "toggle",
        activeClass: "active",
        closeOnOutside: true,
        closeOnEsc: true,
        innerSelector: ".popup-comment__content",
        closeBtn: ".popup-comment__close"
      },
      {
        trigger: ".js-share-btn",
        target: ".share-popup__container",
        behavior: "toggle",
        activeClass: "active",
        closeOnOutside: true,
        closeOnEsc: true,
        closeBtn: ".share-popup__close"
      },
      {
        trigger: ".tab-btn",
        behavior: "activate",
        groupSelector: ".tab-btn",
        activeClass: "active",

        onActiveChange: function (isActive, triggerEl) {
          if (isActive) {
            document.querySelectorAll('.tab-panel').forEach(panel => {
              panel.classList.remove('active');
            });

            const targetId = triggerEl.getAttribute('data-target');

            if (targetId) {
              const targetPanel = document.querySelector(targetId);
              if (targetPanel) {
                targetPanel.classList.add('active');
              }
            }
          }
        }
      },
      {
        trigger: ".btn-hide",
        target: ".tab-panel__container",
        behavior: "toggle",
        activeClass: "hide",
        onActiveChange: function (isActive, triggerEl) {
          triggerEl.innerText = isActive ? "Xem thêm" : "Ẩn đi";
        }
      }
    ]);
    extractHeadingData('.detail-content');
    const tocData = extractHeadingData('.detail-content');
    if (tocData && tocData.length > 0) {
      renderDynamicList(tocData, '.table-heading__body ul');
    }
    initAutoImageZoom('.js-gallery-main', 1.5);
    // 🟡 roll to the top
    initScrollToTop();
    // ✨ 4️⃣ HIỆU ỨNG ẢNH & REVEAL
    applyImageEnhancements();
    initRevealEffect();
    initFormValidation();
  });
});

// 🔁 Cập nhật khi include hoặc slick load lại
document.addEventListener("includesLoaded", () => applyImageEnhancements());
$(document).on("init reInit afterChange", ".slick-slider", function () {
  applyImageEnhancements(this);
});

// Public literature header. Kept independent from legacy menu selectors.
function initLiteratureHeader() {
  const header = document.querySelector('[data-public-header]');
  const toggle = header?.querySelector('[data-public-menu-toggle]');
  if (!header || !toggle || toggle.dataset.bound === 'true') return;

  toggle.dataset.bound = 'true';

  const closeMenu = () => {
    header.classList.remove('is-menu-open');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'Mở menu');
  };

  toggle.addEventListener('click', () => {
    const willOpen = !header.classList.contains('is-menu-open');
    header.classList.toggle('is-menu-open', willOpen);
    toggle.setAttribute('aria-expanded', String(willOpen));
    toggle.setAttribute('aria-label', willOpen ? 'Đóng menu' : 'Mở menu');
  });

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') closeMenu();
  });

  document.addEventListener('click', event => {
    if (!header.contains(event.target)) closeMenu();
  });

  window.addEventListener('resize', () => {
    if (window.innerWidth > 768) closeMenu();
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initLiteratureHeader);
} else {
  initLiteratureHeader();
}

$(document).ready(() => {
  const btnSubmitContactForm = $("#btn-submit-contact-form");
  if (btnSubmitContactForm.length) {
    btnSubmitContactForm.on("click", function (e) {
      const form = $(this).closest("form");
      const name = form.find("input[name='name']").val()?.trim();
      const phone_number = form.find("input[name='phone_number']").val()?.trim();
      const email = form.find("input[name='email']").val()?.trim();
      const address = form.find("input[name='address']").val()?.trim();
      const description = form.find("textarea[name='description']").val()?.trim();
      const recaptchaResponse = form.find("textarea[name='g-recaptcha-response']").val()?.trim();
      if (form.length && validateForm(form[0])) {
        $.ajax({
          url: "/ajax.php",
          method: "POST",
          data: {
            'op': 'contact_form',
            'name': name,
            'phone_number': phone_number,
            'email': email,
            'address': address,
            'description': description,
            'g-recaptcha-response': recaptchaResponse
          },
          success: function (response) {
            if (response?.success === true) {
              alert(response.message);
              form[0].reset();
            } else {
              alert(response.message || "Đã xảy ra lỗi khi gửi thông tin. Vui lòng thử lại.");
            }
          },
          error: function () {
            alert("Đã xảy ra lỗi khi gửi thông tin. Vui lòng thử lại.");
          }
        });
      } else {
        alert("Vui lòng điền đầy đủ thông tin hợp lệ trước khi gửi.");
      }
    });
  }

  const btnSubmitSchedule = $("#btn-submit-schedule");
  if (btnSubmitSchedule.length) {
    btnSubmitSchedule.on("click", function (e) {
      const form = $(this).closest("form");
      const name = form.find("input[name='name']").val()?.trim();
      const phone_number = form.find("input[name='phone_number']").val()?.trim();
      const email = form.find("input[name='email']").val()?.trim();
      const address = form.find("input[name='address']").val()?.trim();
      const description = form.find("textarea[name='description']").val()?.trim();
      const recaptchaResponse = form.find("textarea[name='g-recaptcha-response']").val()?.trim();
      if (form.length && validateForm(form[0])) {
        $.ajax({
          url: "/ajax.php",
          method: "POST",
          data: {
            'op': 'contact_form',
            'name': name,
            'phone_number': phone_number,
            'email': email,
            'address': address,
            'description': description,
            'g-recaptcha-response': recaptchaResponse
          },
          success: function (response) {
            if (response?.success === true) {
              alert(response.message);
              form[0].reset();
            } else {
              alert(response.message || "Đã xảy ra lỗi khi gửi thông tin. Vui lòng thử lại.");
            }
          },
          error: function () {
            alert("Đã xảy ra lỗi khi gửi thông tin. Vui lòng thử lại.");
          }
        });
      } else {
        alert("Vui lòng điền đầy đủ thông tin hợp lệ trước khi gửi.");
      }
    });
  }
});

const currentLang = window.location.pathname.startsWith('/en/') || window.location.pathname === '/en' ? 'en' : 'vi';
const copyMessages = {
  vi: {
    default: 'Sao chép liên kết',
    success: 'Đã sao chép!'
  },
  en: {
    default: 'Copy link',
    success: 'Copied!'
  }
};
const msg = copyMessages[currentLang];
document.querySelectorAll('.js-share-btn[data-type="copy"]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    const url = window.location.href;

    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(url).then(function () {
        showCopyFeedback(btn);
      }).catch(function () {
        fallbackCopy(url, btn);
      });
    } else {
      fallbackCopy(url, btn);
    }
  });
});

function fallbackCopy(text, btn) {
  const textarea = document.createElement('textarea');
  textarea.value = text;
  textarea.style.cssText = 'position:fixed;top:-9999px;left:-9999px;opacity:0';
  document.body.appendChild(textarea);
  textarea.focus();
  textarea.select();
  try {
    document.execCommand('copy');
    showCopyFeedback(btn);
  } catch (e) {
    console.error('Copy thất bại:', e);
  }
  document.body.removeChild(textarea);
}

function showCopyFeedback(btn) {
  btn.title = msg.success;
  btn.classList.add('copied');
  setTimeout(function () {
    btn.title = msg.default;
    btn.classList.remove('copied');
  }, 2000);
}

document.querySelectorAll('.m-menu__toggle').forEach(function (toggle) {
  toggle.addEventListener('click', function () {
    const children = this.closest('.m-menu__item, .m-menu__child-item')
      .querySelector('.m-menu__children');
    if (children) {
      children.classList.toggle('open');
      this.textContent = children.classList.contains('open') ? '−' : '+';
    }
  });
});

var categoryChildSelect = document.getElementById('categoryChildSelect');

if (categoryChildSelect) {
    categoryChildSelect.addEventListener('change', function () {
        var val = this.value;

        if (val !== '-1' && val !== '') {
            window.location.href = val;
        }
    });
}

const serviceContent = document.querySelector(".service-content");

if (serviceContent) {

  const articleCategoryId = serviceContent.dataset.categoryId;
  const lang = serviceContent.dataset.lang;

  $(".service-sidebar__item").click(function () {
    $(".service-sidebar__item").removeClass("active");
    $(this).addClass("active");
    loadArticleGroup($(this).data("group"));
  });

  $(".filter-select").change(function () {
    loadArticleGroup($(this).val());
  });

 function loadArticleGroup(groupId) {

    $.ajax({
        url: "/ajax.php",
        type: "POST",
        dataType: "json",

        data: {
            op: "article_group",
            group_id: groupId,
            category_id: articleCategoryId,
            lang: lang
        },

        success: function (res) {

            if (res.status != 1) {
                console.error("Article group error:", res);
                return;
            }

            /*
             * Swiper nằm bên trong serviceContent
             */
            const swiperEl = serviceContent.querySelector(
                ".js-slider-4cols"
            );

            if (!swiperEl) {
                console.error(
                    "Không tìm thấy .js-slider-4cols"
                );
                return;
            }

            /*
             * Lấy swiper-wrapper
             */
            const swiperWrapper = swiperEl.querySelector(
                ".swiper-wrapper"
            );

            if (!swiperWrapper) {
                console.error(
                    "Không tìm thấy .swiper-wrapper"
                );
                return;
            }

            /*
             * Lấy instance Swiper hiện tại
             */
            const swiper = swiperEl.swiper;

            /*
             * Destroy Swiper cũ
             *
             * false ở tham số thứ 2 để giữ lại HTML
             */
            if (swiper) {
                swiper.destroy(true, false);
            }

            /*
             * Thay danh sách bài viết
             */
            swiperWrapper.innerHTML = res.html;

            /*
             * Khởi tạo lại Swiper
             */
            initSwiperSlider({
                mainSelector: ".js-slider-4cols",

                /*
                 * Phải là phần tử cha của Swiper
                 */
                wrapperSelector: ".js-slider-wrapper",

                minSlides: 0,

                loop: false,

                navigation: {
                    nextEl: ".swiper-button-next",
                    prevEl: ".swiper-button-prev"
                },

                pagination: {
                    el: ".swiper-pagination",
                    clickable: true
                },

                breakpoints: {
                    320: {
                        slidesPerView: 2,
                        spaceBetween: 10
                    },

                    768: {
                        slidesPerView: 3,
                        spaceBetween: 15
                    },

                    1024: {
                        slidesPerView: 4,
                        spaceBetween: 20
                    }
                }
            });
        },

        error: function (xhr, status, error) {
            console.error("AJAX error:", error);
        }
    });
}
}

document.addEventListener('DOMContentLoaded', function () {
  const select = document.getElementById('articleGroupSelect');
  if (!select) return;
  select.addEventListener('change', function () {
    const base = this.dataset.baseUrl;
    const group = this.value.trim();
    if (group === '') {
      window.location.href = base;
    } else {
      window.location.href = `${base}?group=${encodeURIComponent(group)}`;
    }
  });
});

function count(type) {
    $.ajax({
        type: "POST",
        url: "/ajax.php",
        dataType: "json",
        data: {
            op: "count",
            type: type
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    var HEADER_OFFSET = 110; // chiều cao menu cố định (px)

    function scrollToTarget(hash) {
        if (!hash) return;

        var target = document.querySelector(hash);
        if (!target) return;

        var targetPosition = target.getBoundingClientRect().top + window.pageYOffset;
        var offsetPosition = targetPosition - HEADER_OFFSET;

        window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
        });
    }

    // Xử lý khi load trang có sẵn #hash trên URL (vd: /gioi-thieu#ve-chung-toi)
    if (window.location.hash) {
        // Đợi 1 chút để đảm bảo layout/ảnh đã render xong trước khi tính vị trí
        setTimeout(function () {
            scrollToTarget(window.location.hash);
        }, 100);
    }

    // Xử lý khi click vào các link nội bộ dạng href="#id" hoặc href="/trang#id"
    document.querySelectorAll('a[href*="#"]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            var url = new URL(link.href, window.location.origin);
            var hash = url.hash;

            if (!hash) return;

            // Chỉ can thiệp nếu link trỏ đến cùng trang hiện tại
            var sameOrigin = url.origin === window.location.origin;
            var samePath = url.pathname === window.location.pathname;

            if (sameOrigin && samePath) {
                var target = document.querySelector(hash);
                if (target) {
                    e.preventDefault();
                    scrollToTarget(hash);
                    // Cập nhật URL mà không reload
                    history.pushState(null, '', hash);
                }
            }
            // Nếu khác path (điều hướng sang trang khác kèm #hash) -> để trình duyệt tự chuyển trang,
            // đoạn code "load trang có sẵn #hash" phía trên sẽ xử lý tiếp khi trang mới load xong.
        });
    });
});
