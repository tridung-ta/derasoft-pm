// js rate star
function initStarRating(containerSelector = '.rate-stars', starSelector = '.star', activeClass = 'active') {
  const containers = document.querySelectorAll(containerSelector);
  if (!containers.length) return;
  containers.forEach(container => {
    const stars = Array.from(container.querySelectorAll(starSelector));
    if (!stars.length) return;
    const defaultActiveCount = container.querySelectorAll(`.${activeClass}`).length;
    container.dataset.rating = defaultActiveCount || 0;
    stars.forEach((star, index) => {
      if (star.dataset._ratingBound === "true") return;
      star.dataset._ratingBound = "true";
      star.style.cursor = 'pointer';
      star.addEventListener('click', () => {
        const currentRating = index + 1;
        container.dataset.rating = currentRating;
        stars.forEach((s, i) => {
          if (i < currentRating) {
            s.classList.add(activeClass);
          } else {
            s.classList.remove(activeClass);
          }
        });

      });
    });
  });
}
initStarRating('.popup-comment__content .rate-stars', '.star', 'active');

$(document).ready(() => {
  const btnSubmitCommentForm = $("#btn-submit-comment-form");
  if (btnSubmitCommentForm.length) {
    btnSubmitCommentForm.on("click", function (e) {
      e.preventDefault(); // thêm
      const form = $(this).closest("form");
      const star = form.find(".rate-stars .star.active").length; 
      const fullname = form.find("input[name='fullname']").val()?.trim();
      const tel = form.find("input[name='tel']").val()?.trim();
      const email = form.find("input[name='email']").val()?.trim();
      const address = form.find("input[name='address']").val()?.trim();
      const details = form.find("textarea[name='details']").val()?.trim();
      const pid = form.find("input[name='pid']").val()?.trim();
      const object_name = form.find("input[name='object_name']").val()?.trim();
      console.log(object_name);
      const type = form.find("input[name='type']").val()?.trim();
      const recaptchaResponse = form.find("textarea[name='g-recaptcha-response']").val()?.trim();
      if (form.length && validateForm(form[0])) {
        $.ajax({
          url: "/ajax.php",
          method: "POST",
          data: { op: 'comment_form', star, fullname, tel, email, address, details, pid, object_name, type, 'g-recaptcha-response': recaptchaResponse },
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
