/**
 * Human-Friendly Error Handler & Translator
 * Converts technical HTTP status codes and error objects into polite, actionable Indonesian messages.
 */

export const HTTP_ERROR_MESSAGES = {
    400: {
        title: 'Permintaan Tidak Sesuai',
        message: 'Data yang dikirimkan tidak sesuai format yang diharapkan sistem. Silakan periksa kembali.',
    },
    401: {
        title: 'Sesi Telah Berakhir',
        message: 'Sesi akses Anda telah berakhir demi keamanan. Silakan muat ulang halaman atau login kembali.',
    },
    403: {
        title: 'Akses Dibatasi',
        message: 'Akun Anda tidak memiliki wewenang atau hak akses untuk melakukan tindakan ini.',
    },
    404: {
        title: 'Data Tidak Ditemukan',
        message: 'Data atau halaman yang Anda minta tidak tersedia atau telah dipindahkan.',
    },
    419: {
        title: 'Halaman Kedaluwarsa',
        message: 'Token keamanan halaman telah berakhir karena tidak ada aktivitas. Silakan muat ulang (refresh) halaman.',
    },
    422: {
        title: 'Isian Formulir Belum Tepat',
        message: 'Terdapat kolom formulir yang belum lengkap atau formatnya belum sesuai. Periksa kolom yang bertanda merah.',
    },
    429: {
        title: 'Terlalu Banyak Permintaan',
        message: 'Sistem mendeteksi terlalu banyak aksi dalam waktu singkat. Mohon tunggu beberapa detik sebelum mencoba lagi.',
    },
    500: {
        title: 'Kendala Layanan Server',
        message: 'Terjadi gangguan sementara pada server kami. Tim teknis telah diberi tahu dan sedang memperbaikinya.',
    },
    503: {
        title: 'Mode Pemeliharaan',
        message: 'Layanan sedang dalam pemeliharaan terjadwal untuk peningkatan performa. Silakan coba kembali sesaat lagi.',
    },
};

/**
 * Format any error into a friendly { title, message } object
 */
export function formatErrorMessage(error) {
    if (!error) {
        return {
            title: 'Terjadi Kendala',
            message: 'Tindakan belum dapat diselesaikan. Silakan coba kembali sesaat lagi.',
        };
    }

    // If string message passed
    if (typeof error === 'string') {
        return {
            title: 'Perhatian',
            message: error,
        };
    }

    // If HTTP Response status exists
    const status = error.status || error.response?.status;
    if (status && HTTP_ERROR_MESSAGES[status]) {
        return HTTP_ERROR_MESSAGES[status];
    }

    // Network / Offline Error
    if (error.message && (error.message.includes('Network Error') || !window.navigator.onLine)) {
        return {
            title: 'Koneksi Terputus',
            message: 'Tidak dapat terhubung ke server. Pastikan perangkat Anda terhubung ke internet.',
        };
    }

    // Laravel Validation Errors Bag (Object of field => messages)
    if (error.errors && typeof error.errors === 'object') {
        const firstField = Object.keys(error.errors)[0];
        const firstMessage = error.errors[firstField]?.[0] || 'Periksa isian formulir Anda.';
        return {
            title: 'Isian Belum Lengkap',
            message: firstMessage,
        };
    }

    return {
        title: 'Kendala Sistem',
        message: error.message || 'Operasi gagal diselesaikan. Silakan hubungi admin jika kendala berlanjut.',
    };
}
