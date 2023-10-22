$(document).ready(function () {

    const host = 'http://localhost/hms/dataCureService/endpoints';

    var hospital_id = $('#hospital_id').html()
    var staff_code = $('#staff_code').html()
    var token = $('#token').html();
    var password = $('#password_status').html();
    console.log('token pass ' + password)

    if (password == 'N') {
        sweet('warning', 'Password Update', 'Kindly Update Your Password')
    }
    console.log('hospital_id ' + hospital_id)
    console.log('staff ' + staff_code)
    console.log('token ' + token)
    console.log('token pass ' + password)

    function sweet(icon, title, text) {
        Swal.fire({
            icon: icon,
            title: title,
            text: text,
            allowEscapeKey: false,
      allowOutsideClick: false,
        })
        
    }

})