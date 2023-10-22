$(document).ready(function () {

  const host = 'http://localhost/hms/dataCureService/endpoints';
  $('#submit').on('click', function () {
    var hospital_id = $('#hospital_id').val()
    var staff_email = $('#staff_email').val()
    var staff_password = $('#staff_password').val()

    if (hospital_id != "" && staff_email != "" && staff_password != "") {
      staffLogin(hospital_id, staff_email, staff_password)
    } else {
      sweet('question', 'Empty Fields', 'Complete the fields in order to proceed')
    }

  })


  function staffLogin(hospital_id, staff_email, staff_password) {
    loadingSweet('Validating Details.....')
    var settings = {
      "url": host + "/loginStaff.php",
      "method": "POST",
      "timeout": 0,
      "headers": {
        "Content-Type": "application/json"
      },
      "data": JSON.stringify({
        "hospital_id": hospital_id,
        "staff_email": staff_email,
        "staff_password": staff_password
      }),
    };

    $.ajax(settings).done(function (response) {
      console.log(response)
      if (response.message == 'success') {
        Swal.close()
        window.location.href = 'auth?token=' + response.accessToken + '&staff_email=' + response.staff_email + '&hospital_id=' + response.hospital_id + '&hospital_name=' + response.hospital_name + '&staff_code=' + response.staff_code + '&staff_name=' + response.staff_name + '&password_status=' + response.password_status;
      } else {
        sweet('error', 'Invalid Credentials', response.message)
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
})