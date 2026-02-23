import { Navigate, Outlet } from "react-router-dom";
import { useStateContext } from "../Context/ContextProvider";

export default function GuestLayout() {
    const { token, user } = useStateContext();

    if (token) {
        // Redirect based on user role
        switch (user?.role) {
            case 'admin':
                return <Navigate to="/admin" />;
            default:
                return <Navigate to="/" />;
        }
    }

    return (
        <div>
            <div>Guest Layout</div>
            <Outlet />
        </div>
    );
}