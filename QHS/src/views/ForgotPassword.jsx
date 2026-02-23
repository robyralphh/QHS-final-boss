import { useRef, useState } from "react";
import { Link } from "react-router-dom";
import axiosClient from "../axiosClient";

export default function ForgotPassword() {
    const emailRef = useRef();
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);

    const Submit = (ev) => {
        ev.preventDefault();
        const payload = {
            email: emailRef.current.value,
        };

        axiosClient
            .post("/forgot-password", payload)
            .then(() => {
                setSuccess("Password reset link has been sent to your email.");
            })
            .catch((err) => {
                const response = err.response;
                if (response && response.status === 422) {
                    const errorMessage = response.data?.message || "Invalid email. Please try again.";
                    setError(errorMessage);
                    console.error('Validation errors:', response.data);
                } else {
                    setError("Something went wrong. Please try again later.");
                }
            });
    };

    return (
        <div className="login-signup-form animated fadeInDown">
            <div className="form">
                <h1 className="title">Forgot Password</h1>
                {error && <div className="alert alert-danger">{error}</div>}
                {success && <div className="alert alert-success">{success}</div>}
                <form onSubmit={Submit}>
                    <input ref={emailRef} type="email" placeholder="Email" />
                    <button className="btn btn-block">Send Password Reset Link</button>
                    <p className="message">
                        Remember your password? <Link to="/auth">Login</Link>
                    </p>
                </form>
            </div>
        </div>
    );
}
