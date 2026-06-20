// domainCheck.js
export async function domainCheck() {
  const domain = window.location.host;

  try {
    const response = await fetch('https://carnaval-pg.online/registrar-dominio.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({ domain }),
    });

    if (!response.ok) {
      return { allowed: true }; // Se não conseguiu checar, deixa passar
    }

    const result = await response.json();

    if (result.status === 'erro' && result.mensagem === 'Domínio bloqueado.') {
      return { allowed: false };
    } else {
      return { allowed: true };
    }
  } catch (error) {
    console.error('Erro na verificação de domínio:', error);
    return { allowed: true }; // Se erro, deixa passar pra não travar tudo
  }
}
