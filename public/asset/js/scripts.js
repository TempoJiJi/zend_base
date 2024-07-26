var GLOBAL_WEB_ROOT =
  window.location.protocol + "//" + window.location.hostname + ":" + window.location.port;
var url = window.location;
var apiParam = {};
var apiUrl = GLOBAL_WEB_ROOT;
var pageNo = 1;
var bFF = navigator.userAgent.toLowerCase().indexOf("firefox") !== -1;

function IsJsonString(str) {
  try {
    JSON.parse(str);
  } catch (e) {
    return false;
  }
  return true;
}

// Check response status
function checkStatus(data) {
  if (IsJsonString(data)) {
    data = JSON.parse(data);
  }

  if (typeof data.status === "undefined") {
    alert("Return data error");
    location.href = "/";
    return;
  }
  if (data.status == false) {
    if (data.code == 100002) {
      alert("Please login and try again.");
      location.href = "/logout";
      exit(0);
    }
    if (data.code == 100001) {
      alert(data.msg);
      location.href = "/";
      exit(0);
    }
  }
  return;
}

function aclFailed() {
  window.alert("You don't have access, please login again");
  window.location.href = GLOBAL_WEB_ROOT + "auth/logout";
}

function companyFailed() {
  window.alert("Company data not found, please login again");
  window.location.href = GLOBAL_WEB_ROOT + "auth/logout";
}

function missingMenuId() {
  window.alert(
    "Something went wrong, please try again later (Missing Menu ID)"
  );
  window.location.href = "/";
}

function errorBox(msg) {
  window.alert(msg);
  window.location.href = "/";
}

function getUrlParameter(sParam) {
  var sPageURL = window.location.search.substring(1),
    sURLVariables = sPageURL.split("&"),
    sParameterName,
    i;

  for (i = 0; i < sURLVariables.length; i++) {
    sParameterName = sURLVariables[i].split("=");

    if (sParameterName[0] === sParam) {
      return sParameterName[1] === undefined
        ? true
        : decodeURIComponent(sParameterName[1]);
    }
  }
}

// Validates that the input string is a valid date formatted as "YYYY-MM-DD"
function isValidDate(dateString) {
  // First check for the pattern
  if (!/^\d{4}\-\d{1,2}\-\d{1,2}$/.test(dateString)) return false;

  // Parse the date parts to integers
  var parts = dateString.split("-");
  var year = parseInt(parts[0], 10);
  var month = parseInt(parts[1], 10);
  var day = parseInt(parts[2], 10);

  // Check the ranges of month and year
  if (year < 1000 || year > 3000 || month == 0 || month > 12) return false;

  var monthLength = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

  // Adjust for leap years
  if (year % 400 == 0 || (year % 100 != 0 && year % 4 == 0))
    monthLength[1] = 29;

  // Check the range of the day
  return day > 0 && day <= monthLength[month - 1];
}

function setCookie(name, value, days) {
  var expires = "";
  if (days) {
    var date = new Date();
    date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
    expires = "; expires=" + date.toUTCString();
  }
  document.cookie = name + "=" + (value || "") + expires + "; path=/";
}
function getCookie(name) {
  var nameEQ = name + "=";
  var ca = document.cookie.split(";");
  for (var i = 0; i < ca.length; i++) {
    var c = ca[i];
    while (c.charAt(0) == " ") c = c.substring(1, c.length);
    if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
  }
  return null;
}
function eraseCookie(name) {
  document.cookie = name + "=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;";
}


function formatDate(date) {
  var fuk_date = date.split('/');
  if (fuk_date.length != 3) {
    return date;
  }

  var d = new Date(`${fuk_date[1]}-${fuk_date[0]}-${fuk_date[2]}`);
  month = '' + (d.getMonth() + 1),
    day = '' + d.getDate(),
    year = d.getFullYear();

  if (month.length < 2)
    month = '0' + month;
  if (day.length < 2)
    day = '0' + day;

  return [year, month, day].join('-');
}
