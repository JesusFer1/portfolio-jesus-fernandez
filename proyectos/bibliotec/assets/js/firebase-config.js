import { initializeApp } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-app.js";

import {
    getAuth
} from "https://www.gstatic.com/firebasejs/11.6.1/firebase-auth.js";

import {
    getFirestore
} from "https://www.gstatic.com/firebasejs/11.6.1/firebase-firestore.js";


const firebaseConfig = {
    apiKey: "AIzaSyBZvcX--2_0-OuVwMAO2EBjSsFo4PFbi1w",
    authDomain: "bibliotec-81ebf.firebaseapp.com",
    databaseURL: "https://bibliotec-81ebf-default-rtdb.firebaseio.com",
    projectId: "bibliotec-81ebf",
    storageBucket: "bibliotec-81ebf.firebasestorage.app",
    messagingSenderId: "461189824001",
    appId: "1:461189824001:web:dbde8ef24db26b61f28a00"
};

const app = initializeApp(firebaseConfig);

export const auth = getAuth(app);

export const db = getFirestore(app);