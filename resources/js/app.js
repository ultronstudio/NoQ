import './echo';
import * as bootstrap from 'bootstrap';
import Swal from 'sweetalert2';
import QRCode from 'qrcode';

window.bootstrap = bootstrap;
window.Swal = Swal;
window.QRCode = QRCode;

const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3500,
    timerProgressBar: true,
});

document.addEventListener('livewire:init', () => {
    Livewire.on('toast', ({ variant, message }) => {
        toast.fire({
            icon: variant === 'success' ? 'success' : 'error',
            title: message,
        });
    });
});
