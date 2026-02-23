import { createContext, useState, useEffect,useContext } from "react";

const StateContext = createContext({
    user: null,
    token: null,
    setUser: () => {},
    setToken: () => {},
});

export const ContextProvider = ({ children }) => {
    // Initialize user and token from localStorage (if available)
    const [user, setUser] = useState(() => {
        const storedUser = localStorage.getItem("USER");
        return storedUser ? JSON.parse(storedUser) : null;
    });
    const [token, _setToken] = useState(localStorage.getItem("ACCESS_TOKEN"));

    // Update localStorage whenever token changes
    const setToken = (token) => {
        _setToken(token);
        if (token) {
            localStorage.setItem("ACCESS_TOKEN", token);
        } else {
            localStorage.removeItem("ACCESS_TOKEN");
        }
    };

    // Update localStorage whenever user changes
    useEffect(() => {
        if (user) {
            localStorage.setItem("USER", JSON.stringify(user));
        } else {
            localStorage.removeItem("USER");
        }
    }, [user]);

    return (
        <StateContext.Provider
            value={{
                user,
                token,
                setUser,
                setToken,
            }}
        >
            {children}
        </StateContext.Provider>
    );
};

export const useStateContext = () => useContext(StateContext);