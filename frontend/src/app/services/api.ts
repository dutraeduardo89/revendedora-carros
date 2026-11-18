import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { timeout, catchError } from 'rxjs/operators';

@Injectable({ providedIn: 'root' })
export class ApiService {
  private baseUrl = 'http://localhost:9090';

  constructor(private http: HttpClient) { }

  private getHeaders() {
    const user = localStorage.getItem('usuarioLogado') || 'admin';
    return new HttpHeaders().set('X-User-Login', user);
  }

  private request(method: string, url: string, body: any = null): Observable<any> {
    const headers = this.getHeaders();
    const req = method === 'GET' ? this.http.get(url, { headers }) : 
                method === 'POST' ? this.http.post(url, body, { headers }) :
                method === 'PUT' ? this.http.put(url, body, { headers }) :
                this.http.delete(url, { headers });
                
    return req.pipe(
      timeout(5000),
      catchError(err => {
        console.error('Erro na requisição:', err);
        return throwError(() => err);
      })
    );
  }

  login(dados: any) { return this.request('POST', `${this.baseUrl}/login.php`, dados); }
  registrar(dados: any) { return this.request('POST', `${this.baseUrl}/usuarios.php`, dados); }
  getCarros() { return this.request('GET', `${this.baseUrl}/carros.php`); }
  cadastrarCarro(dados: any) { return this.request('POST', `${this.baseUrl}/carros.php`, dados); }
  atualizarCarro(dados: any) { return this.request('PUT', `${this.baseUrl}/carros.php`, dados); }
  excluirCarro(id: number) { return this.request('DELETE', `${this.baseUrl}/carros.php?id=${id}`); }
  getAuxiliares(tipo: string) { return this.request('GET', `${this.baseUrl}/auxiliares.php?acao=${tipo}`); }
  cadastrarAuxiliar(tipo: string, dados: any) { return this.request('POST', `${this.baseUrl}/auxiliares.php?acao=${tipo}`, dados); }
  editarAuxiliar(tipo: string, id: number, dados: any) { return this.request('PUT', `${this.baseUrl}/auxiliares.php?acao=${tipo}&id=${id}`, dados); }
  excluirAuxiliar(tipo: string, id: number) { return this.request('DELETE', `${this.baseUrl}/auxiliares.php?acao=${tipo}&id=${id}`); }
  getStats() { return this.request('GET', `${this.baseUrl}/stats.php`); }
  alterarSenha(dados: any) { return this.request('POST', `${this.baseUrl}/senha.php`, dados); }
}
