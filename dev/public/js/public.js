import "../css/public.scss";

jQuery(function ($) {
  var sktprConfig = typeof sktpr_settings !== 'undefined' ? sktpr_settings : {};
  var sktprStrings = sktprConfig.strings || {};
  var sktprString = function (key, fallback) {
    return sktprStrings[key] || fallback;
  };

  // Grid-based masonry keeps review order left-to-right while allowing cards
  // with different heights to pack without forcing equal-height rows.
  var sktprMasonryFrame = 0;
  var sktprObservedCards = typeof WeakSet === 'function' ? new WeakSet() : null;
  var sktprMasonryResizeObserver = typeof ResizeObserver === 'function'
    ? new ResizeObserver(function (entries) {
        entries.forEach(function (entry) {
          var reviews = entry.target.closest('.sktpr-shortcode--masonry .sktpr-shortcode-reviews');
          if (reviews) sktprQueueMasonry();
        });
      })
    : null;

  function sktprLayoutMasonry(reviews) {
    var cards = Array.prototype.filter.call(reviews.children, function (child) {
      return child.classList.contains('sktpr-shortcode-card');
    });

    if (!cards.length) {
      reviews.classList.remove('is-masonry-ready');
      return;
    }

    var shortcode = reviews.closest('.sktpr-shortcode');
    var gap = shortcode
      ? parseFloat(window.getComputedStyle(shortcode).getPropertyValue('--sktpr-sc-gap')) || 0
      : 0;

    reviews.classList.add('is-masonry-ready');
    var rowHeight = parseFloat(window.getComputedStyle(reviews).gridAutoRows) || 1;

    var spans = cards.map(function (card) {
      return Math.max(1, Math.ceil((card.getBoundingClientRect().height + gap) / rowHeight));
    });

    cards.forEach(function (card, index) {
      card.style.gridRowEnd = 'span ' + spans[index];

      if (sktprMasonryResizeObserver && (!sktprObservedCards || !sktprObservedCards.has(card))) {
        sktprMasonryResizeObserver.observe(card);
        if (sktprObservedCards) sktprObservedCards.add(card);
      }
    });
  }

  function sktprQueueMasonry() {
    window.cancelAnimationFrame(sktprMasonryFrame);
    sktprMasonryFrame = window.requestAnimationFrame(function () {
      var roots = Array.prototype.slice.call(
        document.querySelectorAll('.sktpr-shortcode--masonry .sktpr-shortcode-reviews')
      );

      roots.forEach(sktprLayoutMasonry);

      Array.prototype.forEach.call(
        document.querySelectorAll('.sktpr-shortcode--spotlight .sktpr-shortcode-reviews'),
        sktprLayoutSpotlight
      );

      Array.prototype.forEach.call(
        document.querySelectorAll('.sktpr-shortcode--reels .sktpr-shortcode-reviews'),
        sktprSyncReelsArrows
      );

      Array.prototype.forEach.call(
        document.querySelectorAll('.sktpr-shortcode--marquee .sktpr-shortcode-reviews'),
        sktprLayoutMarquee
      );
    });
  }

  // Marquee layout: the cards are cloned so the track holds two identical
  // halves, each at least as wide as the viewport, and the CSS animation
  // slides it by one half for a seamless loop. Clones are hidden from
  // assistive tech and the keyboard but stay clickable for the lightbox.
  var sktprMarqueeSpeed = 40; // px per second

  function sktprLayoutMarquee(track) {
    var viewport = track.parentElement;
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var originals = Array.prototype.filter.call(track.children, function (child) {
      return child.classList.contains('sktpr-shortcode-card') && !child.classList.contains('is-marquee-clone');
    });

    if (!originals.length || reduce) {
      Array.prototype.forEach.call(track.querySelectorAll(':scope > .is-marquee-clone'), function (clone) { clone.remove(); });
      track.classList.remove('is-marquee-ready');
      track.removeAttribute('data-sktpr-marquee-reps');
      return;
    }

    var setWidth = originals.reduce(function (sum, card) {
      return sum + card.getBoundingClientRect().width + parseFloat(window.getComputedStyle(card).marginRight || 0);
    }, 0);
    if (!setWidth) return;

    var reps = Math.max(1, Math.ceil(viewport.clientWidth / setWidth));
    var hasClones = track.querySelector(':scope > .is-marquee-clone');
    if (hasClones && String(reps) === track.getAttribute('data-sktpr-marquee-reps')) return;

    Array.prototype.forEach.call(track.querySelectorAll(':scope > .is-marquee-clone'), function (clone) { clone.remove(); });

    var fragment = document.createDocumentFragment();
    for (var copy = 1; copy < reps * 2; copy++) {
      originals.forEach(function (card) {
        var clone = card.cloneNode(true);
        clone.classList.add('is-marquee-clone');
        clone.setAttribute('aria-hidden', 'true');
        clone.removeAttribute('id');
        Array.prototype.forEach.call(clone.querySelectorAll('a, button, video, [tabindex]'), function (el) {
          el.setAttribute('tabindex', '-1');
        });
        fragment.appendChild(clone);
      });
    }
    track.appendChild(fragment);
    track.setAttribute('data-sktpr-marquee-reps', String(reps));
    track.style.setProperty('--sktpr-sc-marquee-duration', Math.round((setWidth * reps) / sktprMarqueeSpeed) + 's');
    track.classList.add('is-marquee-ready');
  }

  // Accordion layout: opening an item closes the others in the same display,
  // and pauses any video left playing in the item that closed.
  document.addEventListener('toggle', function (event) {
    var item = event.target;
    if (!item.matches || !item.matches('.sktpr-shortcode--accordion .sktpr-shortcode-accordion-item')) return;

    if (!item.open) {
      Array.prototype.forEach.call(item.querySelectorAll('video'), function (video) { video.pause(); });
      return;
    }

    Array.prototype.forEach.call(item.parentElement.querySelectorAll(':scope > .sktpr-shortcode-accordion-item[open]'), function (other) {
      if (other !== item) other.open = false;
    });
  }, true);

  // Reels layout: arrows scroll the horizontal track by about a view, and
  // are disabled at either end.
  function sktprSyncReelsArrows(track) {
    var frame = track.closest('.sktpr-shortcode-reels-frame');
    if (!frame) return;
    var max = track.scrollWidth - track.clientWidth - 2;
    var prev = frame.querySelector('.sktpr-shortcode-reels-prev');
    var next = frame.querySelector('.sktpr-shortcode-reels-next');
    if (prev) prev.disabled = track.scrollLeft <= 2;
    if (next) next.disabled = track.scrollLeft >= max;
    frame.classList.toggle('is-scrollable', max > 0);
  }

  $(document).on('click', '[data-sktpr-reels-dir]', function () {
    var track = this.closest('.sktpr-shortcode-reels-frame').querySelector('.sktpr-shortcode-reviews');
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    track.scrollBy({ left: Number(this.getAttribute('data-sktpr-reels-dir')) * track.clientWidth * 0.9, behavior: reduce ? 'auto' : 'smooth' });
  });

  document.addEventListener('scroll', function (event) {
    if (event.target.matches && event.target.matches('.sktpr-shortcode--reels .sktpr-shortcode-reviews')) {
      sktprSyncReelsArrows(event.target);
    }
  }, true);

  // Muted preview playback on hover, for pointers that can hover.
  var sktprReelsHover = window.matchMedia && window.matchMedia('(hover: hover)').matches &&
    !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (sktprReelsHover) {
    $(document).on('mouseenter', '.sktpr-shortcode--reels .sktpr-shortcode-tile.has-media', function () {
      var video = this.querySelector('.sktpr-media-thumb video');
      if (video) {
        var playing = video.play();
        if (playing && playing.catch) playing.catch(function () {});
      }
    }).on('mouseleave', '.sktpr-shortcode--reels .sktpr-shortcode-tile.has-media', function () {
      var video = this.querySelector('.sktpr-media-thumb video');
      if (video) {
        video.pause();
        video.currentTime = 0.1;
      }
    });
  }

  // Spotlight layout: the first card spans every row the other cards occupy
  // in the second column, so the count is refreshed whenever cards change.
  function sktprLayoutSpotlight(reviews) {
    var cards = reviews.querySelectorAll(':scope > .sktpr-shortcode-card').length;
    reviews.style.setProperty('--sktpr-sc-spotlight-rows', String(Math.max(1, cards - 1)));
  }

  sktprQueueMasonry();

  // Slider layout. Swiper is enqueued only where a slider renders; without it
  // (e.g. inside page-builder previews) the CSS scroll-snap fallback applies.
  var sktprReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function sktprSliderConfig(shortcode) {
    try {
      return JSON.parse(shortcode.getAttribute('data-sktpr-slider') || '{}');
    } catch (error) {
      return {};
    }
  }

  // Cards per view. Matches the grid layout's 900px/600px breakpoints; inside
  // the admin live preview the chosen device frame decides instead, because
  // the "desktop" frame there can be narrower than the tablet one.
  function sktprSliderColumns(shortcode, config) {
    var frame = shortcode.closest('[data-preview-device]');
    var device = frame ? frame.getAttribute('data-preview-device') : '';
    var width = window.innerWidth;
    var columns = config.columns;

    if (device === 'mobile' || (!device && width <= 600)) {
      columns = config.mobileColumns;
    } else if (device === 'tablet' || (!device && width <= 900)) {
      columns = config.tabletColumns;
    }

    return Math.max(1, Number(columns) || 1);
  }

  function sktprPrepareSlides(shortcode) {
    var reviews = shortcode.querySelector('.sktpr-shortcode-reviews');
    if (!reviews) return;

    reviews.classList.add('swiper-wrapper');
    Array.prototype.forEach.call(reviews.children, function (child) {
      if (child.classList.contains('sktpr-shortcode-card')) {
        child.classList.add('swiper-slide');
      }
    });
  }

  // Rewind to the first card from the end only once every page is loaded;
  // until then reaching the end fetches more reviews instead.
  function sktprSyncSliderRewind(shortcode) {
    if (shortcode.sktprSwiper) {
      shortcode.sktprSwiper.params.rewind = shortcode.getAttribute('data-has-more') !== '1';
    }
  }

  function sktprApplySliderColumns(swiper) {
    var shortcode = swiper.el.closest('.sktpr-shortcode');
    var columns = sktprSliderColumns(shortcode, sktprSliderConfig(shortcode));

    if (swiper.params.slidesPerView !== columns) {
      swiper.params.slidesPerView = columns;
      swiper.params.slidesPerGroup = columns;
      return true;
    }
    return false;
  }

  function sktprInitSlider(shortcode) {
    if (shortcode.sktprSwiper || typeof window.Swiper !== 'function') return;

    var slider = shortcode.querySelector('.sktpr-shortcode-slider');
    if (!slider) return;

    var config = sktprSliderConfig(shortcode);
    var columns = sktprSliderColumns(shortcode, config);
    var prev = shortcode.querySelector('.sktpr-shortcode-slider-prev');
    var next = shortcode.querySelector('.sktpr-shortcode-slider-next');
    var dots = shortcode.querySelector('.sktpr-shortcode-slider-dots');

    sktprPrepareSlides(shortcode);
    slider.classList.add('swiper');

    var options = {
      // Arrows, dots and autoplay move a full "page" of cards at a time.
      slidesPerView: columns,
      slidesPerGroup: columns,
      spaceBetween: Number(config.gap) || 0,
      speed: 450,
      watchOverflow: true,
      grabCursor: true,
      rewind: shortcode.getAttribute('data-has-more') !== '1',
      keyboard: { enabled: true, onlyInViewport: true },
      a11y: {
        prevSlideMessage: sktprString('slider_prev', 'Previous reviews'),
        nextSlideMessage: sktprString('slider_next', 'Next reviews')
      },
      on: {
        beforeResize: function (swiper) {
          sktprApplySliderColumns(swiper);
        },
        reachEnd: function () {
          sktprLoadMoreSlides(shortcode);
        }
      }
    };

    if (prev && next) {
      options.navigation = { prevEl: prev, nextEl: next, disabledClass: 'is-disabled' };
    }
    if (dots) {
      options.pagination = { el: dots, clickable: true };
    }
    if (config.autoplay && !sktprReducedMotion) {
      options.autoplay = {
        delay: Math.max(1000, Number(config.delay) || 5000),
        disableOnInteraction: false,
        pauseOnMouseEnter: true
      };
    }

    shortcode.sktprSwiper = new window.Swiper(slider, options);
    shortcode.classList.add('is-slider-ready');
  }

  function sktprRefreshSlider(shortcode, resetPosition) {
    var swiper = shortcode.sktprSwiper;
    if (!swiper || swiper.destroyed) return;

    sktprPrepareSlides(shortcode);
    sktprSyncSliderRewind(shortcode);
    sktprApplySliderColumns(swiper);
    swiper.update();
    if (resetPosition) {
      swiper.slideTo(0, 0);
    }
    if (swiper.autoplay && swiper.params.autoplay && swiper.params.autoplay.enabled && !swiper.autoplay.running) {
      swiper.autoplay.start();
    }
  }

  function sktprLoadMoreSlides(shortcode) {
    if (shortcode.getAttribute('data-has-more') !== '1' || shortcode.classList.contains('is-loading')) return;

    $(shortcode).trigger('sktpr:load-more');
  }

  function sktprInitSliders() {
    Array.prototype.forEach.call(document.querySelectorAll('.sktpr-shortcode--slider'), sktprInitSlider);
  }

  // Replaced previews (admin, builders) must not leave autoplay timers behind.
  function sktprDestroySliders(node) {
    var sliders = node.matches('.sktpr-shortcode--slider') ? [node] : node.querySelectorAll('.sktpr-shortcode--slider');
    Array.prototype.forEach.call(sliders, function (shortcode) {
      if (shortcode.sktprSwiper && !document.contains(shortcode)) {
        shortcode.sktprSwiper.destroy(true, false);
        shortcode.sktprSwiper = null;
      }
    });
  }

  sktprInitSliders();
  window.addEventListener('load', sktprInitSliders);

  // Switching the admin preview device may not resize the slider itself.
  if (typeof MutationObserver === 'function') {
    new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        Array.prototype.forEach.call(mutation.target.querySelectorAll('.sktpr-shortcode--slider'), function (shortcode) {
          sktprRefreshSlider(shortcode, false);
        });
      });
    }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-preview-device'], subtree: true });
  }

  if (typeof MutationObserver === 'function') {
    var sktprMasonryMutationObserver = new MutationObserver(function (mutations) {
      var needsLayout = mutations.some(function (mutation) {
        return Array.prototype.some.call(mutation.addedNodes, function (node) {
          return node.nodeType === 1 && (
            node.matches('.sktpr-shortcode--masonry, .sktpr-shortcode-card') ||
            node.querySelector('.sktpr-shortcode--masonry, .sktpr-shortcode-card')
          );
        });
      });

      if (needsLayout) sktprQueueMasonry();

      var hasSlider = mutations.some(function (mutation) {
        return Array.prototype.some.call(mutation.addedNodes, function (node) {
          return node.nodeType === 1 && (
            node.matches('.sktpr-shortcode--slider') || node.querySelector('.sktpr-shortcode--slider')
          );
        });
      });

      if (hasSlider) sktprInitSliders();

      mutations.forEach(function (mutation) {
        Array.prototype.forEach.call(mutation.removedNodes, function (node) {
          if (node.nodeType === 1) sktprDestroySliders(node);
        });
      });
    });

    sktprMasonryMutationObserver.observe(document.documentElement, { childList: true, subtree: true });
  }

  window.addEventListener('resize', function () {
    sktprQueueMasonry();
  });

  document.addEventListener('load', function (event) {
    var reviews = event.target.closest
      ? event.target.closest('.sktpr-shortcode--masonry .sktpr-shortcode-reviews')
      : null;
    if (reviews) sktprQueueMasonry();
  }, true);

  if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(function () {
      sktprQueueMasonry();
    });
  }

  // Media Uploader

  let sktpr_preview     = document.getElementById("sktpr_preview");
  let sktpr_recording   = document.getElementById("sktpr_recording");
  let sktpr_timer       = document.getElementById("sktpr_timer-text");
  let sktpr_startButton = document.getElementById("sktpr_startButton");
  let sktpr_stopButton  = document.getElementById("sktpr_stopButton");
  let sktpr_addButton   = document.getElementById("sktpr_addButton");

  let sktpr_recording_constraints = {
    audio: { noiseSuppression: false },
    video: {
      width: { min: 480, ideal: 800, max: 1280 },
      height: { min: 320, ideal: 470, max: 720 },
      framerate: 30,
    },
  };

  // Get the modal.
  var modal            = document.getElementById("sktpr_video_modal");
  var sktpr_modal_btn   = document.getElementById("sktpr_modal_btn");
  var sktpr_modal_close = document.getElementsByClassName("sktpr_modal_close")[0];
  var sktpr_no_camera   = document.getElementsByClassName("sktpr_no_camera")[0];
  var sktpr_background  = document.getElementsByClassName("sktpr_background")[0];

  if (sktpr_modal_btn) {
    // When the user clicks on <span> (x), close the modal.
    sktpr_background.onclick = function () {
      sktpr_modal_close.click();
    };
    sktpr_modal_close.onclick = function () {
      modal.style.display = "none";
      sktpr_recording.style.display = "none";
      sktpr_addButton.style.display = "none";
    };
    // When the user clicks anywhere outside of the modal, close it.
    window.onclick = function (event) {
      if (event.target == modal) {
        modal.style.display = "none";
      }
    };
    let max_record_time = sktpr_timer.getAttribute("data-maxtime");
    let recordingTimeMS = 1000 * 60 * max_record_time;

    var videoTimerInterval = null;
    function startTimer(duration, sktpr_timer) {
      var timer = duration,
        minutes,
        seconds;
      videoTimerInterval = setInterval(function () {
        minutes = parseInt(timer / 60, 10);
        seconds = parseInt(timer % 60, 10);
        minutes = minutes < 10 ? "0" + minutes : minutes;
        seconds = seconds < 10 ? "0" + seconds : seconds;
        sktpr_timer.innerHTML = minutes + `:` + seconds;
        sktpr_timer.style.display = "inline-block";
        if (--timer < 0) {
          timer = duration;
          clearInterval(videoTimerInterval);
          sktpr_stopButton.click();
        }
      }, 1000);
    }

    sktpr_preview.controls = false;
    sktpr_recording.controls = false;

    var mediaRecorder;
    var chunks = [];
    var localStream = null;
    var containerType = "video/webm"; //defaults to webm but we switch to mp4 on Safari 14.0.2+

    // Shows the in-modal error panel with a specific message and hides the
    // recording controls.
    function sktprShowCameraError(message) {
      sktpr_no_camera.style.display = "flex";
      sktpr_preview.style.display = "none";

      var messageEl = sktpr_no_camera.querySelector(".camera_inner div");
      if (messageEl) messageEl.textContent = message;

      $(".sktpr_record_video_buttons").css({ display: "none" });
    }

    // Maps a getUserMedia rejection to a human-readable explanation.
    function sktprCameraErrorMessage(err) {
      switch (err && err.name) {
        case "NotAllowedError":
        case "PermissionDeniedError":
          return sktprString('camera_blocked', "Camera access was blocked. Please allow camera and microphone permissions for this site in your browser, then try again.");
        case "NotFoundError":
        case "DevicesNotFoundError":
          return sktprString('camera_missing', "No camera or microphone was found on this device.");
        case "NotReadableError":
        case "TrackStartError":
          return sktprString('camera_busy', "Your camera appears to be in use by another application. Close it and try again.");
        default:
          return sktprString('camera_error', "Could not access your camera or microphone.") + (err && err.name ? " (" + err.name + ")" : "");
      }
    }

    // When the user clicks on the button, open the modal.
    sktpr_modal_btn.onclick = function (e) {
      e.preventDefault();
      modal.style.display = "block";
      sktpr_preview.style.display = "block";
      sktpr_startButton.style.display = "block";

      // Browsers only expose mediaDevices in secure contexts (HTTPS or
      // localhost), so check for it before touching getUserMedia.
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        sktprShowCameraError(
          window.isSecureContext === false
            ? sktprString('https_required', "Camera recording requires a secure connection. Please open this page over HTTPS and try again.")
            : sktprString('camera_unsupported', "Camera recording is not supported in this browser.")
        );
      } else {
        if (window.MediaRecorder == undefined) {
          sktprShowCameraError(
            sktprString('recorder_unsupported', "Video recording is not supported in this browser. Please upload a video file instead.")
          );
        } else {
          navigator.mediaDevices
            .getUserMedia(sktpr_recording_constraints)
            .then(function (stream) {
              localStream = stream;

              sktpr_preview.srcObject = localStream;
              sktpr_preview.play();

              try {
                window.AudioContext =
                  window.AudioContext || window.webkitAudioContext;
                window.audioContext = new AudioContext();
              } catch (e) {
                console.log("Web Audio API not supported.");
              }
            })
            .catch(function (err) {
              sktprShowCameraError(sktprCameraErrorMessage(err));
            });
        }
      }
    };

    function onBtnRecordClicked() {
      if (localStream == null) {
        sktprShowCameraError(
          sktprString('camera_not_ready', "The camera is not ready. Please allow camera access when prompted, or reopen this window to try again.")
        );
      } else {
        sktpr_startButton.disabled = true;
        sktpr_stopButton.disabled = false;

        chunks = [];

        /* use the stream */
        if (typeof MediaRecorder.isTypeSupported == "function") {

          if (MediaRecorder.isTypeSupported("video/webm")) {
            var options = { mimeType: "video/webm" };
          } else if (MediaRecorder.isTypeSupported("video/mp4")) {
            // Safari 14.0.2 has an EXPERIMENTAL version of MediaRecorder enabled by default
            containerType = "video/mp4";
            var options = {
              mimeType: "video/mp4",
              videoBitsPerSecond: 2500000,
            };
          }

          mediaRecorder = new MediaRecorder(localStream, options);
        } else {
          mediaRecorder = new MediaRecorder(localStream);
        }

        mediaRecorder.ondataavailable = function (e) {
          if (e.data && e.data.size > 0) {
            chunks.push(e.data);
          }
        };

        mediaRecorder.onstart = function () {
          var lengthInS = recordingTimeMS / 1000;
          startTimer(lengthInS, sktpr_timer);
        };

        mediaRecorder.onstop = function () {
            
          var recording = new Blob(chunks, { type: mediaRecorder.mimeType });
          sktpr_addButton.href = URL.createObjectURL(recording);
          // Even if they do, they may only support MediaStream.
          sktpr_recording.src = URL.createObjectURL(recording);
          sktpr_recording.controls = true;

          var rand = Math.floor(Math.random() * 10000000);
          switch (containerType) {
            case "video/mp4":
              var name = "video-testimonial-" + rand + ".mp4";
              break;
            default:
              var name = "video-testimonial-" + rand + ".webm";
          }

          sktpr_addButton.addEventListener("click", (e) => {
            e.preventDefault();
            sktpr_recording.style.display = "none";
            sktpr_addButton.style.display = "none";
            document.getElementById("sktpr_startButton_text").innerText =
              sktprString('start_recording', "Start Recording");
            let file = new File([recording], name, {
              type: recording.type,
            });

            let container = new DataTransfer();
            container.items.add(file);
            document.querySelector("#sktpr_client_video_upload").files =
              container.files;

            document.querySelector(".skt-video-wrapper video").src =
              sktpr_addButton.href;
            document.querySelector(
              ".skt-video-wrapper"
            ).style.display = "block";

            sktpr_modal_close.click();
          });

          // sktpr_addButton.innerHTML = 'Download '+name;
          sktpr_addButton.setAttribute("download", name);
          sktpr_addButton.setAttribute("name", name);
        };

        mediaRecorder.start(200);
      }
    }

    sktpr_startButton.addEventListener("click", () => {
      sktpr_no_camera.style.display = "none";
      sktpr_preview.style.display = "block";
      sktpr_recording.style.display = "none";

      sktpr_startButton.style.display = "none";
      sktpr_stopButton.style.display = "inline-block";

      onBtnRecordClicked();
    });

    sktpr_stopButton.addEventListener(
      "click",
      () => {
        sktpr_addButton.style.display = "inline-block";
        sktpr_preview.style.display = "none";
        sktpr_recording.style.display = "block";
        sktpr_timer.style.display = "none";
        sktpr_stopButton.style.display = "none";
        sktpr_startButton.style.display = "block";
        document.getElementById("sktpr_startButton_text").innerText =
          sktprString('record_again', "Record again");
        // stop(sktpr_preview.srcObject);
        clearInterval(videoTimerInterval);
        sktpr_timer.innerText = "";
        onBtnStopClicked();
      },
      false
    );

    function onBtnStopClicked() {
      mediaRecorder.stop();
      sktpr_startButton.disabled = false;
      sktpr_stopButton.disabled = true;
    }
  }

  
  // document.addEventListener('DOMContentLoaded', function() {
    
  //   var reviewForm = document.getElementById('commentform');
  //   if (reviewForm) {
  //     reviewForm.setAttribute('enctype', 'multipart/form-data');
  //   }
  // });

  
    let reviewForm = $('#commentform');
    if (reviewForm.length) {
      reviewForm.attr('enctype', 'multipart/form-data');
    }
  
  // Video file upload: styled button with filename, preview and clear
  var sktprFileInput = document.getElementById("sktpr_file_upload");
  if (sktprFileInput) {
    var sktprFileHint = $(".sktpr_video_upload_hint");
    var sktprFileHintDefault = sktprFileHint.text();
    var sktprFilePreview = $(".inp_file_video");
    var sktprFileClear = $(".sktpr_video_file_clear");

    $(sktprFileInput).on("change", function () {
      var file = this.files[0];
      if (!file) {
        return;
      }

      var name =
        file.name.length > 28 ? file.name.slice(0, 25) + "…" : file.name;
      sktprFileHint.text(name);
      sktprFilePreview.attr("src", URL.createObjectURL(file)).show();
      sktprFileClear.css("display", "inline-flex");
    });

    sktprFileClear.on("click", function () {
      sktprFileInput.value = "";
      sktprFileHint.text(sktprFileHintDefault);
      sktprFilePreview.hide().removeAttr("src");
      sktprFileClear.hide();
    });
  }

  // Image upload: merged selection, previews, per-photo remove (max 5 images)
  var sktprImageInput = document.getElementById("sktpr_image_upload");
  if (sktprImageInput) {
    var SKTPR_MAX_IMAGES = 5;
    var sktprImages = [];
    var sktprImagePreviews = $(".sktpr_image_previews");
    var sktprImageHint = $(".sktpr_image_upload_hint");

    var syncImages = function () {
      var container = new DataTransfer();
      sktprImages.forEach(function (file) {
        container.items.add(file);
      });
      sktprImageInput.files = container.files;

      sktprImagePreviews.empty();
      sktprImages.forEach(function (file, index) {
        var thumb = $('<span class="sktpr_image_thumb"></span>');
        var img = document.createElement("img");
        img.src = URL.createObjectURL(file);
        img.onload = function () {
          URL.revokeObjectURL(img.src);
        };
        var remove = $('<button type="button" class="sktpr_image_remove">&times;</button>')
          .attr('aria-label', sktprString('remove_photo', 'Remove photo'));
        remove.on("click", function () {
          sktprImages.splice(index, 1);
          syncImages();
        });
        thumb.append(img).append(remove);
        sktprImagePreviews.append(thumb);
      });

      sktprImageHint.text(
        sktprImages.length
          ? sktprString('images_added', '%1$d of %2$d added').replace('%1$d', sktprImages.length).replace('%2$d', SKTPR_MAX_IMAGES)
          : sktprString('images_up_to', 'up to %d').replace('%d', SKTPR_MAX_IMAGES)
      );
    };

    $(sktprImageInput).on("change", function () {
      var incoming = Array.prototype.filter.call(this.files, function (file) {
        return /^image\//.test(file.type);
      });

      var room = SKTPR_MAX_IMAGES - sktprImages.length;
      if (incoming.length > room) {
        alert(sktprString('images_limit', 'You can upload up to %d photos.').replace('%d', SKTPR_MAX_IMAGES));
        incoming = incoming.slice(0, room);
      }

      sktprImages = sktprImages.concat(incoming);
      syncImages();
    });
  }

  // Review media lightbox
  var sktprLightbox = null;
  var sktprLightboxItems = [];
  var sktprLightboxIndex = 0;

  function sktprBuildLightbox() {
    if (sktprLightbox) return sktprLightbox;

    sktprLightbox = $(
      '<div class="sktpr-lightbox" role="dialog" aria-modal="true" hidden>' +
        '<div class="sktpr-lightbox-backdrop"></div>' +
        '<button type="button" class="sktpr-lightbox-close">&times;</button>' +
        '<button type="button" class="sktpr-lightbox-nav sktpr-lightbox-prev">&#10094;</button>' +
        '<figure class="sktpr-lightbox-content"></figure>' +
        '<button type="button" class="sktpr-lightbox-nav sktpr-lightbox-next">&#10095;</button>' +
        '<div class="sktpr-lightbox-counter" aria-hidden="true"></div>' +
      '</div>'
    ).appendTo('body');

    sktprLightbox.attr('aria-label', sktprString('review_media', 'Review media'));
    sktprLightbox.find('.sktpr-lightbox-close').attr('aria-label', sktprString('close', 'Close'));
    sktprLightbox.find('.sktpr-lightbox-prev').attr('aria-label', sktprString('previous', 'Previous'));
    sktprLightbox.find('.sktpr-lightbox-next').attr('aria-label', sktprString('next', 'Next'));

    sktprLightbox.on('click', '.sktpr-lightbox-close, .sktpr-lightbox-backdrop', sktprCloseLightbox);
    sktprLightbox.on('click', '.sktpr-lightbox-prev', function () { sktprShowLightboxItem(sktprLightboxIndex - 1); });
    sktprLightbox.on('click', '.sktpr-lightbox-next', function () { sktprShowLightboxItem(sktprLightboxIndex + 1); });

    $(document).on('keydown.sktprLightbox', function (e) {
      if (sktprLightbox.prop('hidden')) return;
      if (e.key === 'Escape') sktprCloseLightbox();
      if (e.key === 'ArrowLeft') sktprShowLightboxItem(sktprLightboxIndex - 1);
      if (e.key === 'ArrowRight') sktprShowLightboxItem(sktprLightboxIndex + 1);
    });

    return sktprLightbox;
  }

  function sktprShowLightboxItem(index) {
    if (!sktprLightboxItems.length) return;

    sktprLightboxIndex = (index + sktprLightboxItems.length) % sktprLightboxItems.length;
    var item = sktprLightboxItems[sktprLightboxIndex];
    var content = sktprLightbox.find('.sktpr-lightbox-content');

    // Emptying removes any playing video so audio stops on navigation
    content.empty();

    if (item.type === 'video') {
      content.append(
        $('<video controls autoplay playsinline></video>').attr('src', item.src)
      );
    } else {
      content.append($('<img alt="" />').attr('src', item.src));
    }

    var multiple = sktprLightboxItems.length > 1;
    sktprLightbox.find('.sktpr-lightbox-nav').toggle(multiple);
    sktprLightbox
      .find('.sktpr-lightbox-counter')
      .text(multiple ? sktprLightboxIndex + 1 + ' / ' + sktprLightboxItems.length : '');
  }

  function sktprCloseLightbox() {
    if (!sktprLightbox) return;
    sktprLightbox.find('.sktpr-lightbox-content').empty();
    sktprLightbox.prop('hidden', true);
    $('body').removeClass('sktpr-lightbox-open');
  }

  $(document).on('click', '.sktpr-media-thumb', function (e) {
    e.preventDefault();

    var clicked = this;
    var thumbs = $(clicked).closest('.sktpr-review-media').find('.sktpr-media-thumb');

    sktprLightboxItems = thumbs
      .map(function () {
        return { type: $(this).data('sktpr-type'), src: $(this).data('sktpr-src') };
      })
      .get();

    sktprBuildLightbox().prop('hidden', false);
    $('body').addClass('sktpr-lightbox-open');
    sktprShowLightboxItem(thumbs.index(clicked));
  });

  // Review filters (star rating + with-media). Reviews are tagged with
  // sktpr-rating-N / sktpr-has-media classes server-side; filtering only
  // toggles visibility.
  var $filterBar = $('.sktpr-review-filters');

  if ($filterBar.length) {
    var sktprActiveRating = 'all';
    var sktprMediaOnly = false;

    var sktprApplyFilters = function () {
      var $reviews = $('.woocommerce-Reviews .commentlist > li');
      var visible = 0;

      $reviews.each(function () {
        var $review = $(this);
        var matches = true;

        if (sktprActiveRating !== 'all' && !$review.hasClass('sktpr-rating-' + sktprActiveRating)) {
          matches = false;
        }

        if (sktprMediaOnly && !$review.hasClass('sktpr-has-media')) {
          matches = false;
        }

        $review.toggle(matches);
        if (matches) visible++;
      });

      $('.sktpr-review-filters-empty').prop('hidden', visible > 0);
    };

    $filterBar.on('click', '[data-sktpr-filter-rating]', function () {
      sktprActiveRating = String($(this).data('sktpr-filter-rating'));
      $filterBar.find('[data-sktpr-filter-rating]').removeClass('is-active');
      $(this).addClass('is-active');
      sktprApplyFilters();
    });

    $filterBar.on('click', '[data-sktpr-filter-media]', function () {
      sktprMediaOnly = !sktprMediaOnly;
      $(this).toggleClass('is-active', sktprMediaOnly).attr('aria-pressed', String(sktprMediaOnly));
      sktprApplyFilters();
    });
  }

  // Saved review shortcodes: filters and Load More are scoped to each instance.
  // The slider has no Load More button; it asks for the next page itself.
  $(document).on('sktpr:load-more', '.sktpr-shortcode--slider', function (event) {
    if (event.target !== this) return;
    sktprRequestShortcodeReviews($(this), $(this), true);
  });

  $(document).on('click', '.sktpr-shortcode [data-sktpr-sc-rating], .sktpr-shortcode [data-sktpr-sc-media], .sktpr-shortcode [data-sktpr-load-more]', function () {
    var $button = $(this);
    sktprRequestShortcodeReviews($button.closest('.sktpr-shortcode'), $button, $button.is('[data-sktpr-load-more]'));
  });

  function sktprRequestShortcodeReviews($shortcode, $button, isLoadMore) {
    var $reviews = $shortcode.find('.sktpr-shortcode-reviews');
    var currentRating = String($shortcode.data('rating') || 'all');
    var mediaOnly = String($shortcode.data('media') || '0') === '1';

    if ($button.is('[data-sktpr-sc-rating]')) {
      currentRating = String($button.data('sktpr-sc-rating'));
      $shortcode.data('rating', currentRating);
      $shortcode.find('[data-sktpr-sc-rating]').removeClass('is-active');
      $button.addClass('is-active');
    }

    if ($button.is('[data-sktpr-sc-media]')) {
      mediaOnly = !mediaOnly;
      $shortcode.data('media', mediaOnly ? '1' : '0');
      $button.toggleClass('is-active', mediaOnly).attr('aria-pressed', String(mediaOnly));
    }

    var nextPage = isLoadMore ? Number($shortcode.data('page') || 1) + 1 : 1;
    var settings = sktprConfig;

    $shortcode.addClass('is-loading').attr('aria-busy', 'true');
    $button.prop('disabled', true);

    var request = {
      action: 'sktpr_shortcode_more',
      id: $shortcode.data('shortcode-id'),
      page: nextPage,
      context_product_id: $shortcode.data('context-product') || 0,
      rating: currentRating,
      media: mediaOnly ? 1 : 0
    };

    // Page-builder displays configured inline carry their signed settings.
    // Read with attr(): data() would parse the JSON and lose the exact string.
    if ($shortcode.is('[data-sktpr-settings]')) {
      request.settings = $shortcode.attr('data-sktpr-settings');
      request.signature = $shortcode.attr('data-sktpr-signature') || '';
    }

    $.post(settings.ajax_url || window.ajaxurl, request).done(function (response) {
      if (!response || !response.success) {
        return;
      }

      var html = response.data.html || $('<p class="sktpr-shortcode-no-results"></p>').text(sktprString('no_reviews', 'No reviews match the selected filters.')).prop('outerHTML');
      if (isLoadMore) {
        $reviews.append(html);
      } else {
        $reviews.html(html);
      }

      sktprQueueMasonry();

      $shortcode.data('page', response.data.page || nextPage);
      $shortcode.attr('data-has-more', response.data.has_more ? '1' : '0');
      var $more = $shortcode.find('[data-sktpr-load-more]');
      $more.toggle(Boolean(response.data.has_more)).prop('disabled', false).text(sktprString('load_more', 'Load More'));
    }).fail(function () {
      $button.prop('disabled', false);
    }).always(function () {
      $button.prop('disabled', false);
      $shortcode.removeClass('is-loading').removeAttr('aria-busy');
      sktprRefreshSlider($shortcode[0], !isLoadMore);
    });
  }

  // Form validation for required video fields
  $('form.comment-form').on('submit', function(e) {
    // Get settings from localized script (if available)
    var settings = sktprConfig;
	var recordingInput = document.getElementById('sktpr_client_video_upload');
	var videoInput = document.getElementById('sktpr_file_upload');
    
    // Check if video recording is required
    if (Boolean(settings.required_video_recording) && recordingInput &&
        recordingInput.files.length === 0 &&
        $('.skt-video-wrapper video').attr('src') === undefined) {
      e.preventDefault();
      alert(sktprString('record_required', 'Please record a video testimonial before submitting your review.'));
      return false;
    }
    
    // Check if file upload is required
    if (Boolean(settings.required_file_upload) && videoInput && videoInput.files.length === 0) {
      e.preventDefault();
      alert(sktprString('video_required', 'Please upload a video file before submitting your review.'));
      return false;
    }

    // Check if at least one photo is required
    var imageUpload = document.getElementById('sktpr_image_upload');
    if (Boolean(settings.required_image_upload) && imageUpload &&
        imageUpload.files.length === 0) {
      e.preventDefault();
      alert(sktprString('image_required', 'Please upload at least one photo before submitting your review.'));
      return false;
    }
  });

});
