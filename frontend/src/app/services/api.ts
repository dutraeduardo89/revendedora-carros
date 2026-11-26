import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Router } from '@angular/router';
import { Observable, throwError } from 'rxjs';
import { timeout, catchError } from 'rxjs/operators';
import { AuthService } from './auth.service';

@Injectable({ providedIn: 'root' })
export class ApiService {
  private baseUrl = 'http://localhost:9090';

  constructor(
    private http: HttpClient,
    private auth: AuthService,
    private router: Router,
  ) {}

  private request(method: string, url: string, body: unknown = null, autenticado = true): Observable<any> {
    let headers = new HttpHeaders();
    const token = this.auth.token();
    if (token) {
      headers = headers.set('Authorization', `Bearer ${token}`);
    }
    const req =
      method === 'GET' ? this.http.get(url, { headers }) :
      method === 'POST' ? this.http.post(url, body, { headers }) :
      method === 'PUT' ? this.http.put(url, body, { headers }) :
      this.http.delete(url, { headers });

    return req.pipe(
      timeout(15000),
      catchError((err) => {
        if (autenticado && err?.status === 401) {
          this.auth.logout();
          this.router.navigate(['/']);
        }
        return throwError(() => err);
      }),
    );
  }

  login(dados: unknown) { return this.request('POST', `${this.baseUrl}/login.php`, dados, false); }
  registrar(dados: unknown) { return this.request('POST', `${this.baseUrl}/usuarios.php`, dados, false); }
  logout() { return this.request('POST', `${this.baseUrl}/logout.php`, {}, true); }
  getCarros() { return this.request('GET', `${this.baseUrl}/carros.php`); }
  getCarro(id: number) { return this.request('GET', `${this.baseUrl}/carros.php?id=${id}`); }
  cadastrarCarro(dados: unknown) { return this.request('POST', `${this.baseUrl}/carros.php`, dados); }
  atualizarCarro(dados: unknown) { return this.request('PUT', `${this.baseUrl}/carros.php`, dados); }
  alterarSituacao(id: number, situacao: string) { return this.request('PUT', `${this.baseUrl}/carros.php?acao=situacao`, { id_carro: id, situacao }); }
  excluirCarro(id: number) { return this.request('DELETE', `${this.baseUrl}/carros.php?id=${id}`); }
  getAuxiliares(tipo: string) { return this.request('GET', `${this.baseUrl}/auxiliares.php?acao=${tipo}`); }
  cadastrarAuxiliar(tipo: string, dados: unknown) { return this.request('POST', `${this.baseUrl}/auxiliares.php?acao=${tipo}`, dados); }
  editarAuxiliar(tipo: string, id: number, dados: unknown) { return this.request('PUT', `${this.baseUrl}/auxiliares.php?acao=${tipo}&id=${id}`, dados); }
  excluirAuxiliar(tipo: string, id: number) { return this.request('DELETE', `${this.baseUrl}/auxiliares.php?acao=${tipo}&id=${id}`); }
  getStats() { return this.request('GET', `${this.baseUrl}/stats.php`); }
  getRegras() { return this.request('GET', `${this.baseUrl}/configuracoes.php`); }
  salvarRegras(dados: unknown) { return this.request('PUT', `${this.baseUrl}/configuracoes.php`, dados); }
  alterarSenha(dados: unknown) { return this.request('POST', `${this.baseUrl}/senha.php`, dados); }
}
