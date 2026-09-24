import { createContext, useState } from "react";

export const AuthContext = createContext(null);

/**
 * Island version: the signed-in user is rendered into the page by Laravel
 * (window.__APP__.user), so there is no /me round-trip or loading spinner.
 */
export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(window.__APP__?.user ?? null);

  return (
    <AuthContext.Provider value={{ user, setUser, loading: false }}>
      {children}
    </AuthContext.Provider>
  );
};
