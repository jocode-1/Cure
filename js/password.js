$(document).ready(function () {


  const host = 'http://localhost/hms/dataCureService/endpoints';

  var hospital_id = $('#hospital_id').html()
  var staff_code = $('#staff_code').html()
  var staff_email = $('#staff_email').html()
  var token = $('#token').html();
  var pass = $('#staff_password').html();


  console.log('hospital_id ' + hospital_id)
  console.log('staff ' + staff_code)
  console.log('token ' + token)
  console.log('staffEmail ' + staff_email)
  // fetchApplication(hospital_id, staff_code, token)
  fetchStaffDetails(hospital_id, staff_code, token)

  // setInterval(() => {
  //   fetchApplication(merchant_id, staff_code, token)
  //   console.log('spin')
  // }, 20000);


  function fetchStaffDetails(hospital_id, staff_code, token) {
    loadingSweet('Fetching Profile.. Please wait')
    var settings = {
      "url": host + "/fetchStaffDetails",
      "method": "POST",
      "timeout": 0,
      "headers": {
        "Authorization": "Bearer " + token,
        "Content-Type": "application/json"
      },
      "data": JSON.stringify({
        "hospital_id": hospital_id.trim(),
        "staff_code": staff_code
      }),
    };

    $.ajax(settings).done(function (response) {
      console.log(response);
      if (response.message == 'success') {
        Swal.close()
        $('#old').html(response.data[0].staff_password);
      } else {
        sweet('error', 'No record found', 'Application ID - ' + application_id + ' not found')
      }
    });

  }


  $('#update_password').on('click', function () {

    // var old_password =  $('#old').html()
    var old_password = $('#old_password').val()
    var password = $('#new_password').val()
    var confirm_password = $('#confirm_password').val()

    console.log('pas ol ' + old_password)
    console.log('pas ne ' + new_password)
    console.log('pas con ' + confirm_password)

    if (password === old_password) {
      sweet('warning', 'Duplicate Password', 'Old and new password cannot be the same.')
    } else if (password != confirm_password) {
      sweet('warning', 'Password mismatch', 'New password and Confirm password are not the same.')
    } else if (password === '' || confirm_password === '' || old_password === '') {
      sweet('warning', 'Empty Fields', 'Empty fields detected.')
    } else {
      change_password(password, staff_email)
    }

  })

  function change_password(staff_password, staff_email) {
    console.log('pas ' + staff_password)
    loadingSweet('Changing Password... Please wait.')
    var settings = {
      "url": host + "/updatePassword.php",
      "method": "POST",
      "timeout": 0,
      "headers": {
        "Authorization": "Bearer " + token,
        "Content-Type": "application/json"
      },
      "data": JSON.stringify({
        "staff_email": staff_email,
        "staff_password": staff_password
      }),
    };

    $.ajax(settings).done(function (response) {
      console.log(response);
      if (response.message == 'success') {
        sweet('success', 'Password Change Successful', 'Password Changed, Signing Out')
        window.location.href = 'logout'
      } else {
        sweet('error', 'Password Change Failed', 'Action failed, please try again')
      }
    });

  }




  function sweet(icon, title, text) {
    Swal.fire({
      icon: icon,
      title: title,
      text: text
    })
  }

  function loadingSweet(text) {
    Swal.fire({
      title: text,
      html: 'Please wait...',
      allowEscapeKey: false,
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading()
      }
    });
  }


  const formatter = new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 0
  })





})