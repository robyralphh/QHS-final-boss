import { useRef, useState } from "react";
import { Link, useNavigate, useLocation } from "react-router-dom";
import axiosClient from "../axiosClient";
import { useStateContext } from "../Context/ContextProvider";

export default function Login() {
    const emailRef = useRef();
    const passwordRef = useRef();
    const { setUser, setToken } = useStateContext();
    const navigate = useNavigate();
    const location = useLocation();

    const [error, setError] = useState(null); // State to manage error messages
    const [unverified, setUnverified] = useState(null);

    // Get the "next" parameter from the query string
    const searchParams = new URLSearchParams(location.search);
    const nextUrl = searchParams.get('next');

    const Submit = (ev) => {
        ev.preventDefault();
        const payload = {
            email: emailRef.current.value,
            password: passwordRef.current.value,
        };

        axiosClient
            .post("/login", payload)
            .then(({ data }) => {
                // Set user and token in context
                setUser(data.user);
                setToken(data.token);
                
                // Redirect to the "next" URL if provided, otherwise use the role-based redirect
                if (nextUrl) {
                    navigate(nextUrl);
                } else {
                    navigate(data.redirectUrl || '/');
                }
            })
            .catch((err) => {
                const response = err.response;
                if (response) {
                    if (response.status === 422) {
                        setError("Invalid email or password. Please try again.");
                    } else if (response.status === 403 && response.data?.needs_verification) {
                        setUnverified({ message: response.data.message });
                    } else {
                        setError(response.data?.message || "Login failed. Please try again.");
                    }
                } else {
                    setError("Login failed. Please try again.");
                    console.error("Login failed:", err);
                }
            });
    };

    return (
        <div className="login-signup-form animated fadeInDown">
            <div className="form">
                <h1 className="title">Login To Your Account</h1>
                {error && <div className="alert alert-danger">{error}</div>}
                {unverified && (
                    <div className="alert alert-warning">
                        <p>{unverified.message}</p>
                        <p style={{ marginTop: 8 }}>
                            <a href="/verify-email" style={{ color: '#0b5ea8' }}>Resend verification email / Verify now</a>
                        </p>
                    </div>
                )}
                <form onSubmit={Submit}>
                    <input ref={emailRef} type="email" placeholder="Email" />
                    <input ref={passwordRef} type="password" placeholder="Password" />
                    <button className="btn btn-block">Login</button>
                    <p className="message">
                        Not Registered? <Link to="register">Create a new account</Link>
                    </p>
                    <p className="message">
                        <Link to="/forgot-password">Forgot Password?</Link>
                    </p>
                </form>
            </div>
        </div>
    );
}