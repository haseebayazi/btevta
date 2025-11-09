{\rtf1\ansi\ansicpg1252\cocoartf1561\cocoasubrtf610
{\fonttbl\f0\fswiss\fcharset0 Helvetica;}
{\colortbl;\red255\green255\blue255;}
{\*\expandedcolortbl;;}
\paperw11900\paperh16840\margl1440\margr1440\vieww10800\viewh8400\viewkind0
\pard\tx566\tx1133\tx1700\tx2267\tx2834\tx3401\tx3968\tx4535\tx5102\tx5669\tx6236\tx6803\pardirnatural\partightenfactor0

\f0\fs24 \cf0 import 'bootstrap'\
\
window.axios = axios\
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'\
\
let token = document.head.querySelector('meta[name="csrf-token"]')\
if (token) \{\
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content\
\} else \{\
    console.error('CSRF token not found')\
\}\
\
window.axios.interceptors.response.use(\
    response => response,\
    error => \{\
        if (error.response?.status === 401) \{\
            window.location.href = '/login'\
        \}\
        return Promise.reject(error)\
    \}\
)}