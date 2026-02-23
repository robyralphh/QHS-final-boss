import { useRef, useState, useEffect } from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import axiosClient from "../axiosClient";

export default function ResetPassword() {
    const passwordRef = useRef();
    const passwordConfirmationRef = useRef();
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);
    const navigate = useNavigate();

    const location = useLocation();
    const queryParams = new URLSearchParams(location.search);
    const token = queryParams.get("token");
    const email = queryParams.get("email");

    // Redirect to login after success message is shown
    useEffect(() => {
        if (success) {
            const timer = setTimeout(() => {
                navigate("/auth");
            }, 2000); // Redirect after 2 seconds
            return () => clearTimeout(timer);
        }
    }, [success, navigate]);

    const Submit = (ev) => {
        ev.preventDefault();
        const payload = {
            email: email,
            token: token,
            password: passwordRef.current.value,
            password_confirmation: passwordConfirmationRef.current.value,
        };

        axiosClient
            .post("/reset-password", payload)
            .then(() => {
                setError(null);
                setSuccess("Password has been reset successfully. Redirecting to login...");
            })
            .catch((err) => {
                const response = err.response;
                if (response && response.status === 422) {
                    const errorMessage = response.data?.message || "Something went wrong. Please check your input and try again.";
                    setError(errorMessage);
                } else {
                    setError("Something went wrong. Please try again later.");
                }
            });
    };

    return (
        <div className="login-signup-form animated fadeInDown">
            <div className="form">
                <h1 className="title">Reset Password</h1>
                {error && <div className="alert alert-danger">{error}</div>}
                {success && <div className="alert alert-success">{success}</div>}
                <form onSubmit={Submit}>
                    <input
                        ref={passwordRef}
                        type="password"
                        placeholder="New Password"
                    />
                    <input
                        ref={passwordConfirmationRef}
                        type="password"
                        placeholder="Confirm New Password"
                    />
                    <button className="btn btn-block">Reset Password</button>
                    <p className="message">
                        Remember your password? <Link to="/auth">Login</Link>
                    </p>
                </form>
            </div>
        </div>
    );
}
