// =========== js 16-6-2025 =========== 
// =========== start ===========

const toggleBtn = document.getElementById('toggleBtnMenu');
const menuList = document.querySelector('#lev ul');

if (toggleBtn && menuList) {
    const isHidden = localStorage.getItem('menuHidden') === 'true';
    if (isHidden) {
        menuList.classList.add('hidden');
    }

    toggleBtn.addEventListener('click', () => {
        menuList.classList.toggle('hidden');
        localStorage.setItem('menuHidden', menuList.classList.contains('hidden'));
    });
}

// js đô table
document.addEventListener("DOMContentLoaded", () => {
  const tables = document.querySelectorAll(".table-scroll-x table");
  if (!tables.length) return;

  const updateTableSize = () => {
    const viewportWidth = window.innerWidth;

    tables.forEach((table, index) => {
      const tableWidth = table.getBoundingClientRect().width;
      const ratio = (tableWidth / viewportWidth) * 100;

      let appliedClass = "";

      if (viewportWidth <= 1024) {
        table.classList.add("originalsize");
        table.classList.remove("upsize");
      } 
      else if (ratio <= 50) {
        table.classList.add("originalsize");
        table.classList.remove("upsize");
      } 
      else {
        table.classList.add("upsize");
        table.classList.remove("originalsize");
      }

    });

  };

  updateTableSize();
  let resizeTimer;
  window.addEventListener("resize", () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(updateTableSize, 150);
  });
});

// js phần ckeditor
document.addEventListener('DOMContentLoaded', function () {
	CKEDITOR.replace('myEditor', {
		toolbar: [
			{ name: 'basicstyles', items: ['Bold', 'Italic'] },
			{ name: 'paragraph', items: ['NumberedList', 'BulletedList'] },
			{ name: 'links', items: ['Link', 'Unlink'] },
			{ name: 'clipboard', items: ['Undo', 'Redo'] }
		],
		height: 200,
		removePlugins: 'elementspath',
		resize_enabled: false
	});
});

// //==== chức năng cuộn chuột khi hover hai mép và grab ====
document.addEventListener("DOMContentLoaded", function () {
	const scrollContainer = document.querySelector(".table-scroll-x");

	if (!scrollContainer) {
		return;
	}

	let scrollInterval = null;

	function startScroll(direction) {
		stopScroll();
		scrollInterval = setInterval(() => {
			scrollContainer.scrollLeft += direction === 'right' ? 10 : -10;
		}, 20);
	}

	function stopScroll() {
		if (scrollInterval) {
			clearInterval(scrollInterval);
			scrollInterval = null;
		}
	}

	scrollContainer.addEventListener("mousemove", function (e) {
		const rect = scrollContainer.getBoundingClientRect();
		const mouseX = e.clientX - rect.left;
		const edgeSize = 120;

		if (mouseX < edgeSize) {
			startScroll('left');
		} else if (mouseX > rect.width - edgeSize) {
			startScroll('right');
		} else {
			stopScroll();
		}
	});

	scrollContainer.addEventListener("mouseleave", stopScroll);



// 	// ====== Thêm drag scroll bằng chuột trái ======

	let isDown = false;
	let startX;
	let scrollLeft;

	scrollContainer.addEventListener('mousedown', function (e) {
		if (e.button !== 0) return; // chỉ bắt chuột trái
		isDown = true;
		scrollContainer.classList.add('dragging');
		startX = e.pageX - scrollContainer.offsetLeft;
		scrollLeft = scrollContainer.scrollLeft;
	});

	scrollContainer.addEventListener('mouseleave', () => {
		isDown = false;
		scrollContainer.classList.remove('dragging');
	});

	scrollContainer.addEventListener('mouseup', () => {
		isDown = false;
		scrollContainer.classList.remove('dragging');
	});

	scrollContainer.addEventListener('mousemove', (e) => {
		if (!isDown) return;
		e.preventDefault();
		const x = e.pageX - scrollContainer.offsetLeft;
		const walk = (x - startX) * 1.5; // tốc độ kéo
		scrollContainer.scrollLeft = scrollLeft - walk;
	});
});
document.addEventListener("DOMContentLoaded", function () {
	const table = document.querySelector(".table-scroll-x table");
	const stickyColIndexes = [0, 1]; // 👉 Đổi số thứ tự cột muốn sticky

	if (!table) {
		return;
	}

	function applyStickyColumns() {
		const thead = table.querySelector("thead");
		const tbody = table.querySelector("tbody");
		const rows = [...thead.rows, ...tbody.rows];

		table.querySelectorAll(".sticky-col").forEach(el => {
			el.classList.remove("sticky-col");
			el.style.left = "";
			el.style.zIndex = "";
			el.style.position = "";
		});

		stickyColIndexes.forEach((colIndex, idx) => {
			let offsetLeft = 0;
			for (let i = 0; i < idx; i++) {
				const refCell = rows[0].cells[stickyColIndexes[i]];
				offsetLeft += refCell.offsetWidth;
			}

			rows.forEach(row => {
				const cell = row.cells[colIndex];
				if (!cell) return;
				cell.classList.add("sticky-col");
				cell.style.position = "sticky";
				cell.style.left = offsetLeft + "px";
				cell.style.zIndex = row.parentElement.tagName === "THEAD" ? 3 : 2;
			});
		});
	}

	applyStickyColumns();
	window.addEventListener("resize", applyStickyColumns);
});

// =========== end ===========

function changlayout() {
	var id_op = $("#cat_id").val();
	var op = "layout";
	var id_pro = $('#id_product').val();
	$.ajax({
		type: 'get',
		url: '/ajax.php',
		data: { op: op, id: id_op, id_pro: id_pro },
		success: function (data11) {
			$('#insert-data').html(data11);
		}
	})
}
function delete_value(ob) {
	var id_check = $(ob).val();
	var id_pro = $('#id_product').val();
	var op = "delete_option";
	$.ajax({
		type: 'get',
		url: '/ajax.php',
		data: { op: op, id: id_check, id_pro: id_pro },
		success: function (data11) {
		}
	})
}

function addinput() {
	var data = '<tr>';
	data = data + '<td colspan="3"><input style="text-align: left;" type="text" name="kichco[]" class="kichco"></td>';
	data = data + '<td ><input style="text-align: left;" type="text" name="chenhlech[]" class="chenhlech"></td>';
	data = data + '</tr>';
	$('.addcolsize').append(data);
}
function showinfooption(ob) {
	var show = $(ob).data('show');
	if (show == true)
		$('.option-show').addClass('active-show');
	else
		$('.option-show').removeClass('active-show');
}
function MM_sortField(targ, osk, sk, sd) {
	var url = document.location.href;
	url = url.replace('&sk=' + osk, '');
	url = url.replace('&sk=' + sk, '');
	url = url.replace('&sd=ASC', '');
	url = url.replace('&sd=DESC', '');
	url = url.replace('&ecode=', '&code=');
	url = url.replace('&rcode=', '&code=');
	eval(targ + ".location='" + url + "&sk=" + sk + "&sd=" + sd + "'");
}
function xoaoption(ob) {

	var name = $(ob).val();
	var op = "size";
	var pid = $(ob).data('id');
	$.ajax({
		type: 'get',
		url: '/ajax.php',
		data: { name: name, op: op, pid: pid },
		success: function (data11) {
		}
	})

}
function MM_jumpMenu(targ, selObj, restore) { //v3.0
	eval(targ + ".location='" + document.location.href + '&ipp=' + selObj.options[selObj.selectedIndex].value + "'");
	if (restore) selObj.selectedIndex = 0;
}

function MM_jump(targ, selObj, url) {
	document.getElementById(targ).src = url + selObj.options[selObj.selectedIndex].value;
}

function showFieldValueControl(selObj, id) {

	var test = selObj.options[selObj.selectedIndex].value;
	console.log(test);

	if (selObj.options[selObj.selectedIndex].value > 3) {
		document.getElementById(id).className = "";
	} else document.getElementById(id).className = "hidden";
}
function showFieldValueControl2(selObj) {
	if (selObj.options[selObj.selectedIndex].value > 3) {
		document.getElementById('value_p').className = "";
		document.getElementById('value_wysiwyg_p').className = "hidden";
		document.getElementById('value_textarea_p').className = "hidden";
		document.getElementById('value_textbox_p').className = "hidden";
	} else if (selObj.options[selObj.selectedIndex].value == 3) {
		document.getElementById('value_p').className = "hidden";
		document.getElementById('value_wysiwyg_p').className = "";
		document.getElementById('value_textarea_p').className = "hidden";
		document.getElementById('value_textbox_p').className = "hidden";
	} else if (selObj.options[selObj.selectedIndex].value == 2) {
		document.getElementById('value_p').className = "hidden";
		document.getElementById('value_wysiwyg_p').className = "hidden";
		document.getElementById('value_textarea_p').className = "";
		document.getElementById('value_textbox_p').className = "hidden";
	} else {
		document.getElementById('value_p').className = "hidden";
		document.getElementById('value_wysiwyg_p').className = "hidden";
		document.getElementById('value_textarea_p').className = "hidden";
		document.getElementById('value_textbox_p').className = "";
	}
}

function showControl(id) {
	document.getElementById(id).className = "";
}

// JavaScript Document
function checkSelect(ojSelect, text) {
	var k;
	for (k = ojSelect.options.length - 1; k >= 0; k--) {
		if (ojSelect.options(k).value == text) {
			ojSelect.options(k).selected = true;
			return true;
		}
	}
	return false;
}


function checkValue(ojSelect, value) {
	var k;
	for (k = ojSelect.options.length - 1; k >= 0; k--) {
		if (ojSelect.options(k).value == value) {
			ojSelect.options(k).selected = true;
			return true;
		}
	}
	return false;
}

function checkRadio(ojSelect, value) {
	var k;
	for (k = ojSelect.length - 1; k >= 0; k--) {
		if (ojSelect(k).value == value) {
			ojSelect(k).checked = true;
			return true;
		}
	}
	return false;
}
function checkCheck(ojSelect, value) {
	var k;
	if (ojSelect.value == value) {
		ojSelect.checked = true;
		return true;
	}
	return false;
}
/* cach su dung 
<script language="javascript" >
	var oj = document['tenForm']['tenSelect'];
	checkSelect(oj,'{TinhTrang}');
</script>
*/

function toggleAllChecks(formName, prefix) {
	n = "all";

	if (prefix) {
		n = prefix + n;
	}
	i = 0;
	e = document.getElementById(n);
	s = e.checked;
	f = document.getElementById(formName);

	while (e = f.elements[i]) {
		if (e.type == "checkbox" && e.id != n) {
			if (!prefix || e.id.indexOf(prefix) != -1) {
				e.checked = s;
			}
		}

		i++;
	}
}

function toggleAllChecksPrefix(formName, prefix) {
	n = "all";

	if (prefix) {
		n = prefix + '_' + n;
	}
	i = 0;
	e = document.getElementById(n);
	s = e.checked;
	f = document.getElementById(formName);

	while (e = f.elements[i]) {
		if (e.type == "checkbox" && e.id != n) {
			if (e.id.indexOf(prefix) != -1) {
				e.checked = s;
			}
		}
		i++;
	}
}

function formSubmit(form, vmod, vdo, vid) {
	f = document.getElementById(form);
	f.mod.value = vmod;
	f.doo.value = vdo;
	f.id.value = vid;
	f.submit();
}

function activeSubmit(form) {
	f = document.forms(form);
	f.plus.value = "active";
	f.submit();
}

function activePlusSubmit(form, act) {
	f = document.forms(form);
	f.plusAct.value = act;
	f.submit();
}

//Variables for addInput functions
var counter = 1;
var limit = 5;
function addInput(divName, fieldType, fieldName, fieldValue) {
	if (counter == limit) {
		alert("Bạn chỉ được quyền tải lên tối đa " + counter + " tập tin mỗi lần!");
	}
	else {
		var newdiv = document.createElement('div');
		newdiv.innerHTML = "<p><input type='" + fieldType + "' name='" + fieldName + "' id='" + fieldName + "' value='" + fieldValue + "'></p>";
		document.getElementById(divName).appendChild(newdiv);
		counter++;
	}
}



function formatCurrency(id) {
	var variable = document.getElementById(id);
	num = variable.value.toString().replace(/\$|\,/g, "");
	if (isNaN(num)) num = "0";
	sign = (num == (num = Math.abs(num)));
	num = Math.floor(num * 100 + 0.50000000001);
	cents = num % 100;
	num = Math.floor(num / 100).toString();
	if (cents < 10) cents = "0" + cents;
	var maxi = Math.floor((num.length - 1) / 3);
	for (var i = 0; i < maxi; i++)
		num = num.substring(0, num.length - 4 * i - 3) + ',' + num.substring(num.length - 4 * i - 3);
	variable.value = (sign ? '' : '-') + num + '.' + cents;
}

function showProgressBar(elementToHide) {
	button = document.getElementById(elementToHide);
	button.style.display = "none";
	bar = document.getElementById("status_bar");
	bar.style.display = "block";
}

/**
 * resets the user picture/avatar in the profile page
 */
function resetAvatarPicture() {
	window.document.updateConfig.avatarId.value = 0;
	// and reload the image path
	window.document.updateConfig.avatarPicture.src = '/images/nophoto.gif';
}

function avatarPictureSelectWindow() {
	width = 500;
	height = 450;

	x = parseInt(screen.width / 2.0) - (width / 2.0);
	y = parseInt(screen.height / 2.0) - (height / 2.0);

	UserPicture = window.open('?op=manage&act=compactListResource&objectId=Avatar', 'AvatarPictureSelect', 'top=' + y + ',left=' + x + ',scrollbars=yes,resizable=yes,toolbar=no,height=' + height + ',width=' + width);
}
function returnAvatarResourceInformation(resId, url) {
	// set the picture id
	parent.opener.document.updateConfig.avatarId.value = resId;
	// and reload the image path
	parent.opener.document.updateConfig.avatarPicture.src = url;
}

/**
 * resets the map picture in the profile page
 */
function resetMapPicture() {
	window.document.updateMap.mapId.value = 0;
	// and reload the image path
	window.document.updateMap.mapPicture.src = '/images/nophoto.gif';
}

function mapPictureSelectWindow() {
	width = 500;
	height = 450;

	x = parseInt(screen.width / 2.0) - (width / 2.0);
	y = parseInt(screen.height / 2.0) - (height / 2.0);

	UserPicture = window.open('?op=manage&act=compactListResource&objectId=Map', 'MapPictureSelect', 'top=' + y + ',left=' + x + ',scrollbars=yes,resizable=yes,toolbar=no,height=' + height + ',width=' + width);
}
function returnMapResourceInformation(resId, url) {
	// set the picture id
	parent.opener.document.updateMap.mapId.value = resId;
	// and reload the image path
	parent.opener.document.updateMap.mapPicture.src = url;
}

function actionSubmit(form, action) {
	f = document.getElementById(form);
	f.plus.value = action;
}

function changePosition(form, id) {
	f = document.getElementById(form);
	control = document.getElementById('position_' + id);
	f.plus.value = 'changePosition';
	f.cId.value = id;
	f.position.value = control.value;
	f.submit();
}

// product option

let optionIndex = 0;

document.addEventListener("DOMContentLoaded", () => {
	const addOptionBtn = document.getElementById("add-option-btn");
	if (!addOptionBtn) return;

	addOptionBtn.addEventListener("click", () => {
		const wrapper = document.getElementById("options-wrapper");
		const currentIndex = wrapper.querySelectorAll(".option-block").length;
		const block = document.createElement("div");
		block.className = "option-block";
		block.innerHTML = `
		<div class="option-name-group">
			<p><label>Tên option:</label><input type="text" name="option_names[]" placeholder="VD: Kích thước" required></p>
		</div>
		<div class="values-wrapper">
			<div class="value-row" style="position: relative;">
				<p>
					<label>Tên giá trị:</label>
					<input type="text" name="value_names[${currentIndex}][]" placeholder="Tên giá trị" required>
					<button type="button" class="remove-value-btn" style="margin-top: 10px; padding: 3px 5px; background-color: white; position: absolute; bottom: 40px; border: none; cursor: pointer;"><i class="fa fa-times fa-lg" style="color: red;"></i></button>
				</p>
				<p>
					<label>Giá trị quy định:</label>
					<input type="number" name="value_modifiers[${currentIndex}][]" value="0" required>
				</p>
			</div>
		</div>
		<button type="button" class="add-value-btn" data-index="${currentIndex}" style="padding: 5px; margin-left: 395px; background-color: #283373; color: white; border-radius: 3px; cursor: pointer; border: none;">Thêm giá trị</button>
		<button type="button" class="remove-option-btn" style="margin-top: 10px; background-color: crimson; color: white; padding: 5px 10px; border-radius: 3px; cursor: pointer; border: none; border: none;">Xóa option</button>
	`;

		wrapper.appendChild(block);
	});


	document.addEventListener("click", function (e) {
		// Tìm đúng nút "Thêm giá trị"
		const addBtn = e.target.closest(".add-value-btn");
		if (addBtn) {
			const idx = addBtn.getAttribute("data-index");
			const parent = addBtn.previousElementSibling;
			const newRow = document.createElement("div");
			newRow.className = "value-row";
			newRow.style.position = "relative";
			newRow.innerHTML = `
			<p>
				<label>Tên giá trị:</label>
				<input type="text" name="value_names[${idx}][]" placeholder="Tên giá trị" required>
				<button type="button" class="remove-value-btn" style="margin-top: 10px; padding: 3px 5px; background-color: white; position: absolute; bottom: 40px; border: none; cursor: pointer;"><i class="fa fa-times fa-lg" style="color: red;"></i></button>
			</p>
			<p>
				<label>Giá trị quy định:</label>
				<input type="number" name="value_modifiers[${idx}][]" value="0" required>
			</p>
			`;

			parent.appendChild(newRow);
			return;
		}

		// Tìm nút "Xóa giá trị"
		const removeBtn = e.target.closest(".remove-value-btn");
		if (removeBtn) {
			const valueRow = removeBtn.closest(".value-row");
			if (valueRow) {
				valueRow.remove();
			}
			return;
		}

		// Tìm nút "Xóa option"
		const removeOptionBtn = e.target.closest(".remove-option-btn");
		if (removeOptionBtn) {
			const optionBlock = removeOptionBtn.closest(".option-block");
			if (optionBlock) {
				if (confirm("Bạn có chắc muốn xóa option này?")) {
					optionBlock.remove();
				}
			}
			return;
		}
	});
});


document.addEventListener("DOMContentLoaded", function () {
	const toggleBtn = document.getElementById("toggle-options-btn");
	const optionWrapper = document.getElementById("options-wrapper");
	const optionAddOption = document.getElementById("add-option-btn");
	if (!toggleBtn || !optionWrapper || !optionAddOption) return;
	toggleBtn.addEventListener("click", function () {
		const isHidden = optionWrapper.style.display === "none";
		optionWrapper.style.display = isHidden ? "block" : "none";
		optionAddOption.style.display = isHidden ? "block" : "none";
		toggleBtn.textContent = isHidden ? "Ẩn Danh Sách Options" : "Hiện Danh Sách Options";
	});
});



// 17 - 6 - 2025 js cho phần tabs responsive 
document.addEventListener("DOMContentLoaded", function () {
	const btnTabMobile = document.getElementById("btnTabMobile");
	const tabs = document.querySelector(".tabs");

	if (!btnTabMobile || !tabs) {
		console.log('❌ Không có phần tử btnTabMobile hoặc tabs');
		return;
	}

	btnTabMobile.addEventListener('click', function () {
		tabs.classList.toggle('open');
	});
});



  $(".js-example-tokenizer").select2({
      tags: true,
      tokenSeparators: [',', ' ']
  })

  $('select').select2({
    createTag: function (params) {
      var term = $.trim(params.term);

      if (term === '') {
        return null;
      }

      return {
        id: term,
        text: term,
        newTag: true // add additional parameters
      }
    }
  });
