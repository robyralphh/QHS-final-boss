import { useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { useStateContext } from "../Context/ContextProvider";

export default function NotAuthorized() {
    const navigate = useNavigate();
    const { user, token } = useStateContext();

    useEffect(() => {
        if (!token) {
            navigate("/auth");
        }
    }, [user, token, navigate]);

    // Function to determine the redirect path based on user role
    const getRedirectPath = () => {
        if (user?.role === "admin") {
            return "/admin";
        } else if (user?.role === "custodian") {
            return "/custodian";
        } else {
            return "/"; // Default to home for other roles or no role
        }
    };

    return (
        <div style={styles.container}>
            <h1 style={styles.title}>404 - Page Not Found</h1>
            <p style={styles.message}>
            "The page you're looking for doesn't exist, or it may have been moved. 
            Don't worry, we're here to help you find your way back!"
            </p>

            <button
                style={styles.button}
                onClick={() => navigate(getRedirectPath())} // Redirect based on role
            >
                Go Back
            </button>
        </div>
    );
}

// Inline styles for the component
const styles = {
    container: {
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        justifyContent: "center",
        height: "100vh",
        backgroundColor: "#f8f9fa",
        color: "#343a40",
        textAlign: "center",
        padding: "20px",
    },
    title: {
        fontSize: "3rem",
        fontWeight: "bold",
        marginBottom: "10px",
    },
    message: {
        fontSize: "1.2rem",
        marginBottom: "20px",
    },
    button: {
        padding: "10px 20px",
        fontSize: "1rem",
        color: "#fff",
        backgroundColor: "maroon",
        border: "none",
        borderRadius: "5px",
        cursor: "pointer",
        transition: "background-color 0.3s ease",
    },
};