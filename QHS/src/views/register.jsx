import { useRef, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import axiosClient from "../axiosClient";
import { useStateContext } from "../Context/ContextProvider";

export default function Register() {
    const nameRef = useRef();
    const emailRef = useRef();
    const passwordRef = useRef();
    const navigate = useNavigate();
    const { setUser, setToken } = useStateContext();
    const [errors, setErrors] = useState(null);
    const [success, setSuccess] = useState(null);
    const [emailStatus, setEmailStatus] = useState(null);

    const Submit = (ev) => {
        ev.preventDefault();
        setErrors(null);
        setSuccess(null);

        const payload = {
            name: nameRef.current.value,
            email: emailRef.current.value,
            password: passwordRef.current.value,
        };

        axiosClient
            .post("/register", payload)
            .then(({ data }) => {
                // Show success message
                setSuccess(data.message || 'Registration successful!');
                // track email send status returned from API
                if (data.hasOwnProperty('email_sent')) {
                    setEmailStatus({ sent: !!data.email_sent, error: data.email_error || null });
                }
                // Redirect after short delay so user can read the alerts
                const redirectTo = data.redirectUrl || '/auth';
                setTimeout(() => {
                    navigate(redirectTo);
                }, 2500);
            })
            .catch((err) => {
                const response = err.response;
                if (response && response.status === 422) {
                    // Show validation errors
                    setErrors(response.data.errors);
                } else {
                    console.error("Registration failed:", err);
                }
            });
    };

    return (
        <div className="login-signup-form animated fadeInDown">
            <div className="form">
                <h1 className="title">Create A New Account</h1>
                {success && (
                    <div style={{
                        padding: "12px",
                        backgroundColor: "#d4edda",
                        color: "#155724",
                        borderRadius: "4px",
                        marginBottom: "16px",
                        border: "1px solid #c3e6cb"
                    }}>
                        <p>{success}</p>
                    </div>
                )}
                {success && (
                    <div style={{
                        padding: "10px",
                        backgroundColor: "#e2f0fb",
                        color: "#0b5ea8",
                        borderRadius: "4px",
                        marginBottom: "16px",
                        border: "1px solid #b6e0fe"
                    }}>
                        <p>Registration complete — please check your email for further instructions.</p>
                    </div>
                )}

                {emailStatus && emailStatus.sent === false && (
                    <div style={{
                        padding: "12px",
                        backgroundColor: "#fff3cd",
                        color: "#856404",
                        borderRadius: "4px",
                        marginBottom: "16px",
                        border: "1px solid #ffeeba"
                    }}>
                        <p>We were unable to send the confirmation email. Please contact the administrator or try again later.</p>
                        {emailStatus.error && (
                            <small style={{ display: 'block', marginTop: 6, color: '#6c6c6c' }}>{emailStatus.error}</small>
                        )}
                    </div>
                )}
                {errors && (
                    <div className="alert alert-danger">
                        {Object.keys(errors).map((key) => (
                            <p key={key}>{errors[key][0]}</p>
                        ))}
                    </div>
                )}
                <form onSubmit={Submit}>
                    <input ref={nameRef} type="text" placeholder="Name" required />
                    <input ref={emailRef} type="email" placeholder="Email" required />
                    <input ref={passwordRef} type="password" placeholder="Password" required />
                    <button className="btn btn-block">Register</button>
                    <p className="message">
                        Already Have An Account? <Link to="../">Login</Link>
                    </p>
                </form>
            </div>
        </div>
    );
}