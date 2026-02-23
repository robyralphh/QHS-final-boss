import { useState, useEffect } from "react";
import { useLocation, useNavigate, useSearchParams } from "react-router-dom";
import axiosClient from "../axiosClient";

export default function VerifyEmail() {
    const location = useLocation();
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();
    const [loading, setLoading] = useState(false);
    const [message, setMessage] = useState("");
    const [error, setError] = useState("");
    const [email, setEmail] = useState("");
    const [token, setToken] = useState("");
    const [verificationSent, setVerificationSent] = useState(false);

    useEffect(() => {
        // Check if we have token and email in URL (from email link)
        const tokenParam = searchParams.get("token");
        const emailParam = searchParams.get("email");
        const statusParam = searchParams.get("status");

        // If redirected from signed backend verification with a status, show appropriate message
        if (statusParam) {
            if (statusParam === 'success') {
                setMessage('Thank you — your email has been verified. You can now login.');
            } else if (statusParam === 'already_verified') {
                setMessage('Your email is already verified. You can login.');
            } else {
                setError('Verification status: ' + statusParam);
            }
            // Pre-fill email if present in query string
            if (emailParam) setEmail(emailParam);
            return;
        }

        if (tokenParam && emailParam) {
            setToken(tokenParam);
            setEmail(emailParam);
            verifyEmailWithToken(emailParam, tokenParam);
        } else if (location.state?.email) {
            setEmail(location.state.email);
        }
    }, [searchParams, location.state]);

    const verifyEmailWithToken = async (emailParam, tokenParam) => {
        setLoading(true);
        setError("");
        
        try {
            const response = await axiosClient.post("/email/verify", {
                email: emailParam,
                token: tokenParam,
            });

            setMessage(response.data.message || "Email verified successfully! Redirecting to login...");
            setVerificationSent(true);
            
            // Redirect to login after 3 seconds
            setTimeout(() => {
                navigate("/auth");
            }, 3000);
        } catch (err) {
            const errorMsg = err.response?.data?.message || "Failed to verify email. Please try again.";
            setError(errorMsg);
        } finally {
            setLoading(false);
        }
    };

    const handleResendEmail = async (e) => {
        e.preventDefault();
        if (!email) {
            setError("Please enter your email address");
            return;
        }

        setLoading(true);
        setError("");

        try {
            const response = await axiosClient.post("/email/resend", {
                email: email,
            });

            setMessage(response.data.message || "Verification email sent! Check your inbox.");
            setVerificationSent(true);
        } catch (err) {
            const errorMsg = err.response?.data?.message || "Failed to send verification email.";
            setError(errorMsg);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="login-signup-form animated fadeInDown">
            <div className="form">
                <h1 className="title">Verify Your Email</h1>

                {error && (
                    <div className="alert alert-danger">
                        <p>{error}</p>
                    </div>
                )}

                {message && (
                    <div style={{
                        padding: "12px",
                        backgroundColor: "#d4edda",
                        color: "#155724",
                        borderRadius: "4px",
                        marginBottom: "16px",
                        border: "1px solid #c3e6cb",
                        textAlign: 'center'
                    }}>
                        <p>{message}</p>
                        <div style={{ marginTop: 12 }}>
                            <button className="btn btn-block" onClick={() => navigate('/auth')}>Go to Login</button>
                        </div>
                    </div>
                )}

                {!verificationSent && !token && (
                    <form onSubmit={handleResendEmail}>
                        <p style={{ marginBottom: "16px", color: "#666" }}>
                            We've sent a verification email to your address. 
                            <br/><br/>
                            Check your inbox and click the verification link to complete your registration.
                            <br/><br/>
                            If you haven't received it, you can request a new one below:
                        </p>

                        <input
                            type="email"
                            placeholder="Enter your email address"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            required
                        />

                        <button 
                            className="btn btn-block" 
                            type="submit"
                            disabled={loading}
                        >
                            {loading ? "Sending..." : "Resend Verification Email"}
                        </button>

                        <p className="message">
                            Already verified? <a href="/auth" style={{ color: "#007bff" }}>Login here</a>
                        </p>
                    </form>
                )}

                {verificationSent && (
                    <div>
                        <p style={{ marginBottom: "16px", color: "#666" }}>
                            Verification email sent! Check your inbox for the verification link.
                        </p>
                        <a href="/auth" style={{ color: "#007bff", textDecoration: "none" }}>
                            <button className="btn btn-block">Back to Login</button>
                        </a>
                    </div>
                )}
            </div>
        </div>
    );
}
