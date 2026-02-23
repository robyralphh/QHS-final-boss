import { useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { useStateContext } from "../Context/ContextProvider";

export default function NotAuthorized(){
    const navigate = useNavigate();
    const { user, token } = useStateContext();

    useEffect(() => {
        if (!token) {
            navigate("/auth");
        } else if (user?.role === "admin") {
            navigate("/admin");
        } else if (user?.role === "custodian"){
            navigate("/custodian");
        }
    }, [user, token, navigate]);


    return (
        <div style={styles.container}>
            <h1 style={styles.title}>403 - Not Authorized</h1>
            <p style={styles.message}>
                You do not have permission to access this page.
            </p>

            <button
                style={styles.button}
                onClick={() => navigate("/")} // Redirect to home
            >
                Go Back to Home
            </button>
        </div>
    );
};


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